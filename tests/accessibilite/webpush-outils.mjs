/*
 * Côté « navigateur » des notifications push, écrit indépendamment du code PHP pour le
 * vérifier : clés d'abonnement, déchiffrement aes128gcm (RFC 8291), contrôle du jeton
 * VAPID (RFC 8292).
 */
import { createDecipheriv, createECDH, createPublicKey, hkdfSync, randomBytes, verify } from 'node:crypto';

const b64u = (buffer) => Buffer.from(buffer).toString('base64url');

export function createSubscriptionKeys() {
    const ecdh = createECDH('prime256v1');
    ecdh.generateKeys();
    const auth = randomBytes(16);

    return { ecdh, auth, p256dh: b64u(ecdh.getPublicKey()), authText: b64u(auth) };
}

export function decrypt(body, keys) {
    const salt = body.subarray(0, 16);
    const idLength = body[20];
    const asPublic = body.subarray(21, 21 + idLength);
    const cipher = body.subarray(21 + idLength);

    const shared = keys.ecdh.computeSecret(asPublic);
    const info = Buffer.concat([Buffer.from('WebPush: info\0'), keys.ecdh.getPublicKey(), asPublic]);
    const ikm = Buffer.from(hkdfSync('sha256', shared, keys.auth, info, 32));
    const cek = Buffer.from(hkdfSync('sha256', ikm, salt, Buffer.from('Content-Encoding: aes128gcm\0'), 16));
    const nonce = Buffer.from(hkdfSync('sha256', ikm, salt, Buffer.from('Content-Encoding: nonce\0'), 12));

    const decipher = createDecipheriv('aes-128-gcm', cek, nonce);
    decipher.setAuthTag(cipher.subarray(cipher.length - 16));
    const plain = Buffer.concat([decipher.update(cipher.subarray(0, cipher.length - 16)), decipher.final()]);
    const end = plain.lastIndexOf(2);
    if (end === -1) {
        throw new Error('Délimiteur de fin absent.');
    }

    return plain.subarray(0, end).toString('utf8');
}

/** Vérifie « Authorization: vapid t=…, k=… » ; renvoie les revendications du jeton. */
export function verifyVapid(header, expectedPublicKey) {
    const match = /^vapid t=([^,]+),\s*k=(.+)$/.exec(header || '');
    if (!match) {
        throw new Error(`En-tête Authorization inattendu : ${header}`);
    }
    const [, token, key] = match;
    if (expectedPublicKey && key !== expectedPublicKey) {
        throw new Error('La clé VAPID annoncée n’est pas celle du site.');
    }
    const [header64, claims64, signature64] = token.split('.');
    const point = Buffer.from(key, 'base64url');
    const publicKey = createPublicKey({
        key: { kty: 'EC', crv: 'P-256', x: b64u(point.subarray(1, 33)), y: b64u(point.subarray(33, 65)) },
        format: 'jwk',
    });
    const valid = verify('sha256', Buffer.from(`${header64}.${claims64}`), { key: publicKey, dsaEncoding: 'ieee-p1363' }, Buffer.from(signature64, 'base64url'));
    if (!valid) {
        throw new Error('Signature VAPID invalide.');
    }

    return JSON.parse(Buffer.from(claims64, 'base64url').toString('utf8'));
}
