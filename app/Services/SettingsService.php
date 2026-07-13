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
        'tax_getf_rate',
        'tax_nhil_rate',
        'tax_vat_rate',
        'default_tax_rate',
        'check_out_time',
    ];

    /** Default fractions when a setting is missing. */
    public const DEFAULT_TAX_GETF = 0.025;
    public const DEFAULT_TAX_NHIL = 0.025;
    public const DEFAULT_TAX_VAT = 0.15;

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
     * Named tax rates as fractions of the room subtotal.
     *
     * @return list<array{key: string, label: string, rate: float}>
     */
    public function taxLines(): array
    {
        return [
            [
                'key' => 'getf',
                'label' => 'GETF',
                'rate' => $this->taxFraction('tax_getf_rate', self::DEFAULT_TAX_GETF),
            ],
            [
                'key' => 'nhil',
                'label' => 'NHIL',
                'rate' => $this->taxFraction('tax_nhil_rate', self::DEFAULT_TAX_NHIL),
            ],
            [
                'key' => 'vat',
                'label' => 'VAT',
                'rate' => $this->taxFraction('tax_vat_rate', self::DEFAULT_TAX_VAT),
            ],
        ];
    }

    /**
     * Combined tax rate as a fraction (sum of GETF + NHIL + VAT).
     */
    public function taxRate(): float
    {
        $total = 0.0;
        foreach ($this->taxLines() as $line) {
            $total += $line['rate'];
        }

        return round($total, 4);
    }

    /** Short label e.g. "GETF 2.50% · NHIL 2.50% · VAT 15.00%" */
    public function taxLinesLabel(): string
    {
        $parts = [];
        foreach ($this->taxLines() as $line) {
            if ($line['rate'] <= 0) {
                continue;
            }
            $parts[] = sprintf('%s %.2f%%', $line['label'], $line['rate'] * 100);
        }

        return $parts === [] ? 'None' : implode(' · ', $parts);
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

        $getf = $this->parsePercentInput((string) ($input['tax_getf_percent'] ?? ''), 'tax_getf_percent', $errors);
        $nhil = $this->parsePercentInput((string) ($input['tax_nhil_percent'] ?? ''), 'tax_nhil_percent', $errors);
        $vat = $this->parsePercentInput((string) ($input['tax_vat_percent'] ?? ''), 'tax_vat_percent', $errors);

        $checkOut = trim((string) ($input['check_out_time'] ?? ''));
        if (!$this->isValidTime($checkOut)) {
            $errors['check_out_time'] = 'Use HH:MM format (e.g. 12:00).';
        }

        if ($errors !== []) {
            return ['ok' => false, 'error' => 'Please fix the highlighted fields.', 'errors' => $errors];
        }

        $combined = round((float) $getf + (float) $nhil + (float) $vat, 4);

        $before = $this->settings->all();
        $after = [
            'hotel_name' => $hotelName,
            'currency' => $currency,
            'tax_getf_rate' => number_format((float) $getf, 4, '.', ''),
            'tax_nhil_rate' => number_format((float) $nhil, 4, '.', ''),
            'tax_vat_rate' => number_format((float) $vat, 4, '.', ''),
            'default_tax_rate' => number_format($combined, 4, '.', ''),
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
     * Values prepared for the settings form (taxes as percent).
     *
     * @return array<string, string>
     */
    public function formDefaults(): array
    {
        $lines = $this->taxLines();
        $byKey = [];
        foreach ($lines as $line) {
            $byKey[$line['key']] = $line['rate'];
        }

        return [
            'hotel_name' => $this->hotelName(),
            'currency' => $this->currency(),
            'tax_getf_percent' => number_format(($byKey['getf'] ?? 0) * 100, 2, '.', ''),
            'tax_nhil_percent' => number_format(($byKey['nhil'] ?? 0) * 100, 2, '.', ''),
            'tax_vat_percent' => number_format(($byKey['vat'] ?? 0) * 100, 2, '.', ''),
            'tax_combined_percent' => number_format($this->taxRate() * 100, 2, '.', ''),
            'tax_lines_label' => $this->taxLinesLabel(),
            'check_out_time' => $this->checkOutTime(),
        ];
    }

    private function taxFraction(string $key, float $default): float
    {
        $raw = $this->get($key);
        if ($raw !== null && is_numeric($raw)) {
            return max(0.0, (float) $raw);
        }

        // Legacy single-rate installs: split only if new keys missing.
        if ($key === 'tax_vat_rate') {
            $legacy = $this->get('default_tax_rate');
            if ($legacy !== null && is_numeric($legacy) && (float) $legacy > 0
                && $this->get('tax_getf_rate') === null
                && $this->get('tax_nhil_rate') === null
            ) {
                return max(0.0, (float) $legacy);
            }
        }

        return max(0.0, $default);
    }

    /**
     * @param array<string, string> $errors
     */
    private function parsePercentInput(string $raw, string $field, array &$errors): ?float
    {
        $raw = trim($raw);
        if ($raw === '' || !is_numeric($raw)) {
            $errors[$field] = 'Enter a valid percentage.';

            return null;
        }

        $percent = (float) $raw;
        if ($percent < 0 || $percent > 100) {
            $errors[$field] = 'Must be between 0 and 100.';

            return null;
        }

        return round($percent / 100, 4);
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
