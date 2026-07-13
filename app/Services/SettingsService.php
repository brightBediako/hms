<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Models\Setting;

final class SettingsService
{
    public const KEYS = [
        'hotel_name',
        'currency',
        'default_tax_rate',
        'check_in_time',
        'check_out_time',
    ];

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    public function __construct(
        private readonly Setting $settings = new Setting(),
        private readonly AuditService $audit = new AuditService(),
    ) {
    }

    public static function forgetCache(): void
    {
        self::$cache = null;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            self::$cache = $this->settings->all();
        } catch (\Throwable) {
            self::$cache = [];
        }

        return self::$cache;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $all = $this->all();
        if (array_key_exists($key, $all) && $all[$key] !== '') {
            return $all[$key];
        }

        return $default;
    }

    public function hotelName(): string
    {
        $name = $this->get('hotel_name');
        if ($name !== null && $name !== '') {
            return $name;
        }

        return (string) Config::app('name', 'Hotel Management System');
    }

    public function currency(): string
    {
        $currency = $this->get('currency');
        if ($currency !== null && $currency !== '') {
            return strtoupper($currency);
        }

        return strtoupper((string) Config::app('currency', 'GHS'));
    }

    /**
     * Tax rate as a fraction (e.g. 0.125 for 12.5%).
     */
    public function taxRate(): float
    {
        $raw = $this->get('default_tax_rate');
        if ($raw !== null && is_numeric($raw)) {
            return max(0.0, (float) $raw);
        }

        return max(0.0, (float) Config::app('tax_rate', 0.125));
    }

    public function checkInTime(): string
    {
        return $this->get('check_in_time', '14:00') ?? '14:00';
    }

    public function checkOutTime(): string
    {
        return $this->get('check_out_time', '12:00') ?? '12:00';
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: true}|array{ok: false, error: string, errors?: array<string, string>}
     */
    public function updateHotelSettings(array $input, ?int $staffId): array
    {
        $errors = [];

        $hotelName = trim((string) ($input['hotel_name'] ?? ''));
        if ($hotelName === '') {
            $errors['hotel_name'] = 'Hotel name is required.';
        } elseif (strlen($hotelName) > 100) {
            $errors['hotel_name'] = 'Hotel name must be at most 100 characters.';
        }

        $currency = strtoupper(trim((string) ($input['currency'] ?? '')));
        if ($currency === '' || !preg_match('/^[A-Z]{3}$/', $currency)) {
            $errors['currency'] = 'Currency must be a 3-letter code (e.g. GHS).';
        }

        $taxPercentRaw = trim((string) ($input['tax_rate_percent'] ?? ''));
        if ($taxPercentRaw === '' || !is_numeric($taxPercentRaw)) {
            $errors['tax_rate_percent'] = 'Enter a valid tax percentage.';
            $taxFraction = null;
        } else {
            $taxPercent = (float) $taxPercentRaw;
            if ($taxPercent < 0 || $taxPercent > 100) {
                $errors['tax_rate_percent'] = 'Tax rate must be between 0 and 100.';
                $taxFraction = null;
            } else {
                $taxFraction = round($taxPercent / 100, 4);
            }
        }

        $checkIn = trim((string) ($input['check_in_time'] ?? ''));
        if (!$this->isValidTime($checkIn)) {
            $errors['check_in_time'] = 'Use HH:MM format (e.g. 14:00).';
        }

        $checkOut = trim((string) ($input['check_out_time'] ?? ''));
        if (!$this->isValidTime($checkOut)) {
            $errors['check_out_time'] = 'Use HH:MM format (e.g. 11:00).';
        }

        if ($errors !== []) {
            return ['ok' => false, 'error' => 'Please fix the highlighted fields.', 'errors' => $errors];
        }

        $before = $this->settings->all();
        $after = [
            'hotel_name' => $hotelName,
            'currency' => $currency,
            'default_tax_rate' => number_format((float) $taxFraction, 4, '.', ''),
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
        ];

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            foreach ($after as $key => $value) {
                $this->settings->set($key, $value);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        self::forgetCache();

        $this->audit->log(
            'settings.update',
            'settings',
            null,
            $this->audit->snapshot(array_intersect_key($before, $after)),
            $after,
            $staffId,
        );

        return ['ok' => true];
    }

    /**
     * Values prepared for the settings form (tax as percent).
     *
     * @return array<string, string>
     */
    public function formDefaults(): array
    {
        return [
            'hotel_name' => $this->hotelName(),
            'currency' => $this->currency(),
            'tax_rate_percent' => number_format($this->taxRate() * 100, 2, '.', ''),
            'check_in_time' => $this->checkInTime(),
            'check_out_time' => $this->checkOutTime(),
        ];
    }

    private function isValidTime(string $value): bool
    {
        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
            return false;
        }
        [$h, $m] = array_map('intval', explode(':', $value));

        return $h >= 0 && $h <= 23 && $m >= 0 && $m <= 59;
    }
}
