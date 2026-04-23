<?php

namespace App\Support;

class SettingsChangeValueFormatter
{
    public static function format(?string $key, mixed $value): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '—';
        }

        return match ($key) {
            'lead_time_days', 'acceptance_lead_time' => self::formatNumberWithSuffix($normalized, 'days'),
            'small_lead_time', 'large_lead_time' => self::formatNumberWithSuffix($normalized, 'working days'),
            'reminder_hours' => self::formatNumberWithSuffix($normalized, 'hours'),
            'small_threshold' => self::formatCurrency($normalized),
            default => $normalized,
        };
    }

    protected static function formatNumberWithSuffix(string $value, string $suffix): string
    {
        $numeric = self::extractNumericValue($value);

        if ($numeric === null) {
            return $value;
        }

        return $numeric . ' ' . $suffix;
    }

    protected static function formatCurrency(string $value): string
    {
        $numeric = self::extractNumericValue($value, true);

        if ($numeric === null) {
            return $value;
        }

        return '₱' . number_format((float) $numeric, 0, '.', ',');
    }

    protected static function extractNumericValue(string $value, bool $allowDecimal = false): string|float|int|null
    {
        $sanitized = preg_replace($allowDecimal ? '/[^0-9.]/' : '/[^0-9]/', '', $value);

        if ($sanitized === null || $sanitized === '') {
            return null;
        }

        if ($allowDecimal) {
            return (float) $sanitized;
        }

        return (int) $sanitized;
    }
}
