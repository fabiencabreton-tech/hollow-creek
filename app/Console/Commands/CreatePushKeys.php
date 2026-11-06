<?php

namespace App\Console\Commands;

use App\Support\WebPush\WebPushCrypto;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Crée les clés VAPID des notifications push et les écrit dans .env, une seule fois.
 * Les changer désabonnerait tous les appareils : la commande refuse si elles existent.
 */
#[Signature('hollow:cles-push {--afficher : Afficher les clés sans toucher au fichier .env}')]
#[Description('Créer les clés des notifications push (VAPID) dans le fichier .env')]
class CreatePushKeys extends Command
{
    public function handle(): int
    {
        $keys = WebPushCrypto::createVapidKeys();

        if ($this->option('afficher')) {
            $this->line('WEBPUSH_PUBLIC_KEY='.$keys['public']);
            $this->line('WEBPUSH_PRIVATE_KEY='.$keys['private']);

            return self::SUCCESS;
        }

        $path = base_path('.env');
        $content = is_file($path) ? (string) file_get_contents($path) : '';

        if (preg_match('/^WEBPUSH_PUBLIC_KEY=\S+/m', $content) && preg_match('/^WEBPUSH_PRIVATE_KEY=\S+/m', $content)) {
            $this->info('Les clés des notifications push existent déjà : rien à faire.');

            return self::SUCCESS;
        }

        if (! is_writable($path)) {
            $this->error('Le fichier .env n’est pas modifiable. Ajoutez ces lignes à la main :');
            $this->line('WEBPUSH_PUBLIC_KEY='.$keys['public']);
            $this->line('WEBPUSH_PRIVATE_KEY='.$keys['private']);

            return self::FAILURE;
        }

        // Lignes vides éventuelles (WEBPUSH_PUBLIC_KEY=) remplacées, sinon ajoutées en fin de fichier.
        $content = preg_replace('/^WEBPUSH_(PUBLIC|PRIVATE)_KEY=[ \t]*\r?\n/m', '', $content) ?? $content;
        $content = rtrim($content)."\n\n# Notifications push (créées par php artisan hollow:cles-push, ne pas changer)\n"
            .'WEBPUSH_PUBLIC_KEY='.$keys['public']."\n"
            .'WEBPUSH_PRIVATE_KEY='.$keys['private']."\n";
        file_put_contents($path, $content);

        $this->info('Clés des notifications push créées dans .env.');

        return self::SUCCESS;
    }
}
