<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Réglages du site modifiables depuis l'administration (inscriptions, date RP…).
 * Les valeurs sont mises en cache ; sans valeur enregistrée, la valeur par défaut
 * de config/hollow.php s'applique.
 */
class Settings
{
    private const CACHE_KEY = 'hollow.settings';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        if (array_key_exists($key, $values)) {
            return $values[$key];
        }

        return $default ?? config("hollow.defaults.{$key}");
    }

    public function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        $this->flush();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    public function registrationsOpen(): bool
    {
        return (bool) $this->get('registrations_open');
    }

    public function rpDate(): string
    {
        return (string) $this->get('rp_date');
    }

    public function maxCharacters(): int
    {
        return max(1, (int) $this->get('max_characters'));
    }

    public function reservationDays(): int
    {
        return max(1, (int) $this->get('reservation_days'));
    }

    public function weather(): string
    {
        return trim((string) $this->get('weather'));
    }

    public function flush(): void
    {
        $this->values = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        try {
            $values = Cache::rememberForever(self::CACHE_KEY, fn (): array => Setting::query()
                ->pluck('value', 'key')
                ->all());
        } catch (Throwable) {
            // Base pas encore installée (première installation) : valeurs par défaut.
            $values = [];
        }

        return $this->values = $values;
    }
}
