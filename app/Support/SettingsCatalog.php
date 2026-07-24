<?php

namespace App\Support;

use App\Models\ProjectRequest;

final class SettingsCatalog
{
    public static function options(): array
    {
        return [
            ['key' => 'lead_time_days', 'label' => 'Required Advance Submission (days)', 'value' => '45 days'],
            ['key' => 'small_threshold', 'label' => 'Small Project Cost Threshold', 'value' => '₱200,000'],
            ['key' => 'small_lead_time', 'label' => 'Small Project Lead Time (working days)', 'value' => '15 days'],
            ['key' => 'large_lead_time', 'label' => 'Large Project Lead Time (working days)', 'value' => '30 days'],
            ['key' => 'acceptance_lead_time', 'label' => 'Acceptance Lead Time (days)', 'value' => '3 days'],
            ['key' => 'reminder_hours', 'label' => 'Approver Inaction Reminder (hours)', 'value' => '48 hours'],
        ];
    }

    public static function optionsWithCurrentValues(): array
    {
        return collect(self::options())
            ->map(function (array $option): array {
                $option['value'] = self::resolveCurrentValue($option['key'], $option['value']);

                return $option;
            })
            ->all();
    }

    public static function resolveCurrentValue(string $key, string $fallback): string
    {
        $latestImplementedRequest = ProjectRequest::query()
            ->where('request_type', 'Settings Change')
            ->where('current_status', 'implemented')
            ->where('meta->setting_change->setting_key', $key)
            ->orderByDesc('completed_at')
            ->orderByDesc('last_transitioned_at')
            ->orderByDesc('id')
            ->first();

        return SettingsChangeValueFormatter::format(
            $key,
            data_get($latestImplementedRequest?->meta, 'setting_change.proposed_value', $fallback)
        );
    }
}
