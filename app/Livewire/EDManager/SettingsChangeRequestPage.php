<?php

namespace App\Livewire\EDManager;

use Livewire\Component;

class SettingsChangeRequestPage extends Component
{
    public array $form = [
        'setting' => '',
        'newValue' => '',
        'reason' => '',
    ];

    public bool $submitted = false;

    public string $submittedId = '';

    public function submit(): void
    {
        $this->validate($this->rules(), $this->messages());

        $this->submittedId = sprintf('SCR-%s-%03d', now()->year, random_int(1, 999));
        $this->submitted = true;
    }

    public function resetForm(): void
    {
        $this->reset(['form', 'submitted', 'submittedId']);
        $this->form = [
            'setting' => '',
            'newValue' => '',
            'reason' => '',
        ];
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function getSettingOptionsProperty(): array
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

    public function getSelectedSettingProperty(): ?array
    {
        return collect($this->settingOptions)->firstWhere('key', $this->form['setting']);
    }

    protected function rules(): array
    {
        return [
            'form.setting' => ['required', 'string'],
            'form.newValue' => ['required', 'string'],
            'form.reason' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.setting.required' => 'Please select a setting to change.',
            'form.newValue.required' => 'Please provide the proposed new value.',
            'form.reason.required' => 'Please provide the justification for the change request.',
        ];
    }

    public function render()
    {
        return view('livewire.ed-manager.settings-change-request-page')
            ->layout('layouts.app', [
                'title' => 'Settings Change Request | EngiStart',
                'header' => 'Settings Change Request',
                'subheader' => 'Submit a system-wide settings change request for VP approval.',
            ]);
    }
}
