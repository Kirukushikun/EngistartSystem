<?php

namespace App\Livewire\ITAdmin;

use App\Models\ProjectRequest;
use App\Support\SettingsChangeValueFormatter;
use Illuminate\Support\Collection;
use Livewire\Component;

class SettingsPage extends Component
{
    public function getSettingsProperty(): Collection
    {
        return collect([
            ['label' => 'Required Advance Submission (days)', 'key' => 'lead_time_days', 'value' => '45 days'],
            ['label' => 'Small Project Cost Threshold', 'key' => 'small_threshold', 'value' => '₱200,000'],
            ['label' => 'Small Project Lead Time (working days)', 'key' => 'small_lead_time', 'value' => '15 days'],
            ['label' => 'Large Project Lead Time (working days)', 'key' => 'large_lead_time', 'value' => '30 days'],
            ['label' => 'Acceptance Lead Time (days)', 'key' => 'acceptance_lead_time', 'value' => '3 days'],
            ['label' => 'Approver Inaction Reminder (hours)', 'key' => 'reminder_hours', 'value' => '48 hours'],
        ])->map(function (array $setting): array {
            $setting['value'] = $this->resolveCurrentSettingValue($setting['key'], $setting['value']);

            return $setting;
        });
    }

    protected function resolveCurrentSettingValue(string $key, string $fallback): string
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

    public function getSystemInformationProperty(): Collection
    {
        return collect([
            ['label' => 'System Name', 'value' => 'EngiStart – Automated Project Initialization System'],
            ['label' => 'Organization', 'value' => 'Brookside Group of Companies'],
            ['label' => 'IT Support Email', 'value' => 'j.montiano@brooksidegroup.org'],
        ]);
    }

    public function render()
    {
        return view('livewire.it-admin.settings-page')
            ->layout('layouts.app', [
                'title' => 'Settings | EngiStart',
                'header' => 'Settings',
                'subheader' => 'Review current system values and control information.',
            ]);
    }
}
