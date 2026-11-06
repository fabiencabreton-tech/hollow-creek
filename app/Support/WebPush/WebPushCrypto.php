<?php

namespace App\Support\WebPush;

use RuntimeException;

/**
 * Chiffrement des notifications push (RFC 8291, « aes128gcm ») et signature VAPID
 * (RFC 8292), avec la seule extension OpenSSL de PHP : pas de dépendance à installer.
 *
 * Clés au format des navigateurs : point public P-256 non compressé (65 octets) et
 * secrets en base64url.
 */
final class WebPushCrypto
{
    /** En-tête DER d'une clé publique P-256 (SubjectPublicKeyInfo), avant le point de 65 octets. */
    private const SPKI_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    public static function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $text): string
    {
        $decoded = base64_decode(strtr($text, '-_', '+/').str_repeat('=', (4 - strlen($text) % 4) % 4), true);

        if ($decoded === false) {
            throw new RuntimeException('Texte base64url invalide.');
        }

        return $decoded;
    }

    /**
     * Nouvelle paire de clés VAPID.
     *
     * @return array{public: string, private: string} en base64url
     */
    public static function createVapidKeys(): array
    {
        [$private, $public] = self::newKeyPair();

        return ['public' => self::base64UrlEncode($public), 'private' => self::base64UrlEncode($private)];
    }

    /**
     * Jeton VAPID (JWT ES256) pour le service push de l'adresse d'abonnement.
     */
    public static function vapidToken(string $endpoint, string $subject, string $publicKey, string $privateKey, int $ttlSeconds = 43200): string
    {
        $parts = parse_url($endpoint);
        $audience = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        $header = self::base64UrlEncode((string) json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::base64UrlEncode((string) json_encode([
            'aud' => $audience,
            'exp' => time() + $ttlSeconds,
            'sub' => $subject,
        ], JSON_UNESCAPED_SLASHES));
        $input = $header.'.'.$claims;

        $key = openssl_pkey_get_private(self::privateKeyPem(self::base64UrlDecode($privateKey), self::base64UrlDecode($publicKey)));
        if ($key === false || ! openssl_sign($input, $der, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Signature VAPID impossible : vérifiez WEBPUSH_PRIVATE_KEY.');
        }

        return $input.'.'.self::base64UrlEncode(self::derToRaw($der));
    }

    /**
     * Corps chiffré d'une notification pour un abonnement (clé p256dh et secret auth du navigateur).
     */
    public static function encrypt(string $payload, string $userPublicKey, string $userAuth): string
    {
        $uaPublic = self::base64UrlDecode($userPublicKey);
        $authSecret = self::base64UrlDecode($userAuth);

        if (strlen($uaPublic) !== 65 || $uaPublic[0] !== "\x04" || strlen($authSecret) !== 16) {
            throw new RuntimeException('Clés d’abonnement invalides.');
        }

        [$asPrivate, $asPublic] = self::newKeyPair();
        $sharedSecret = openssl_pkey_derive(
            openssl_pkey_get_public(self::publicKeyPem($uaPublic)),
            openssl_pkey_get_private(self::privateKeyPem($asPrivate, $asPublic)),
            32,
        );

        if ($sharedSecret === false) {
            throw new RuntimeException('Échange de clés impossible.');
        }

        $ikm = hash_hkdf('sha256', $sharedSecret, 32, "WebPush: info\x00".$uaPublic.$asPublic, $authSecret);
        $salt = random_bytes(16);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);

        // Un seul enregistrement : le texte suivi du délimiteur 0x02.
        $cipher = openssl_encrypt($payload."\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($cipher === false) {
            throw new RuntimeException('Chiffrement impossible.');
        }

        $recordSize = 4096;

        return $salt.pack('N', $recordSize).chr(65).$asPublic.$cipher.$tag;
    }

    /**
     * @return array{0: string, 1: string} secret (32 octets) et point public (65 octets)
     */
    private static function newKeyPair(): array
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        if ($key === false) {
            throw new RuntimeException('Création de clé P-256 impossible.');
        }
        $details = openssl_pkey_get_details($key);
        $ec = $details['ec'];

        return [
            str_pad($ec['d'], 32, "\x00", STR_PAD_LEFT),
            "\x04".str_pad($ec['x'], 32, "\x00", STR_PAD_LEFT).str_pad($ec['y'], 32, "\x00", STR_PAD_LEFT),
        ];
    }

    private static function publicKeyPem(string $point): string
    {
        return self::pem('PUBLIC KEY', hex2bin(self::SPKI_PREFIX).$point);
    }

    /** Clé privée EC (RFC 5915) reconstruite à partir du secret et du point public. */
    private static function privateKeyPem(string $secret, string $point): string
    {
        $der = hex2bin('30770201010420').$secret
            .hex2bin('a00a06082a8648ce3d030107a144034200').$point;

        return self::pem('EC PRIVATE KEY', $der);
    }

    private static function pem(string $label, string $der): string
    {
        return "-----BEGIN {$label}-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END {$label}-----\n";
    }

    /** Signature ECDSA : DER (SEQUENCE de deux INTEGER) vers r||s de 64 octets. */
    private static function derToRaw(string $der): string
    {
        $offset = 2;
        if (ord($der[1]) & 0x80) {
            $offset += ord($der[1]) & 0x7F;
        }
        $raw = '';
        for ($i = 0; $i < 2; $i++) {
            $length = ord($der[$offset + 1]);
            $integer = substr($der, $offset + 2, $length);
            $raw .= str_pad(ltrim($integer, "\x00"), 32, "\x00", STR_PAD_LEFT);
            $offset += 2 + $length;
        }

        return $raw;
    }
}
