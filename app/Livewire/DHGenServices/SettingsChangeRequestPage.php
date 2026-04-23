<?php

namespace App\Livewire\DHGenServices;

use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Support\SettingsChangeValueFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;

class SettingsChangeRequestPage extends Component
{
    public array $form = [
        'setting' => '',
        'newValue' => '',
        'reason' => '',
    ];

    public bool $submitted = false;

    public string $submittedId = '';

    public function updatedFormSetting(string $value): void
    {
        if ($value === '' || trim($this->form['newValue']) === '') {
            return;
        }

        $this->form['newValue'] = SettingsChangeValueFormatter::format($value, $this->form['newValue']);
    }

    public function updatedFormNewValue(string $value): void
    {
        if (($this->form['setting'] ?? '') === '' || trim($value) === '') {
            return;
        }

        $this->form['newValue'] = SettingsChangeValueFormatter::format($this->form['setting'], $value);
    }

    public function openSubmissionReview(): void
    {
        $validated = $this->validate($this->rules(), $this->messages());
        $settingKey = $validated['form']['setting'];
        $formattedProposedValue = SettingsChangeValueFormatter::format($settingKey, $validated['form']['newValue']);

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Submit settings change request?',
            'message' => 'Please confirm the setting change details below before submitting this request for VP Gen Services review.',
            'tone' => 'info',
            'confirmText' => 'Confirm and submit',
            'confirmEvent' => 'dhSettingsChangeSubmissionConfirmed',
            'confirmTarget' => self::class,
            'summary' => [
                ['label' => 'Setting', 'value' => $this->selectedSettingLabel($settingKey)],
                ['label' => 'Current Value', 'value' => $this->selectedSettingValue($settingKey) ?? '—'],
                ['label' => 'Proposed Value', 'value' => $formattedProposedValue],
                ['label' => 'Routing', 'value' => 'VP Gen Services → IT Admin'],
            ],
        ])->to(ConfirmationModal::class);
    }

    #[On('dhSettingsChangeSubmissionConfirmed')]
    public function submit(): void
    {
        $validated = $this->validate($this->rules(), $this->messages());
        $user = Auth::user();
        $settingKey = $validated['form']['setting'];
        $formattedProposedValue = SettingsChangeValueFormatter::format($settingKey, $validated['form']['newValue']);

        abort_unless($user, 403);

        DB::transaction(function () use ($validated, $user, $formattedProposedValue) {
            $projectRequest = ProjectRequest::create([
                'request_number' => $this->generateRequestNumber(),
                'requestor_id' => $user->id,
                'requestor_role' => $user->role,
                'current_status' => 'pending_vp',
                'current_step' => 'vp_gen_services_change_request_review',
                'current_owner_role' => 'vp_gen_services',
                'current_owner_id' => null,
                'is_late' => false,
                'is_exception_flow' => false,
                'title' => 'Settings Change: ' . $this->selectedSettingLabel($validated['form']['setting']),
                'request_type' => 'Settings Change',
                'farm_name' => 'System-wide',
                'purpose' => $validated['form']['reason'],
                'date_needed' => now()->toDateString(),
                'description' => $validated['form']['reason'],
                'submitted_at' => now(),
                'last_transitioned_at' => now(),
                'latest_remarks' => $validated['form']['reason'],
                'meta' => [
                    'setting_change' => [
                        'setting_key' => $validated['form']['setting'],
                        'setting_label' => $this->selectedSettingLabel($validated['form']['setting']),
                        'current_value' => $this->selectedSettingValue($validated['form']['setting']),
                        'proposed_value' => $formattedProposedValue,
                        'submitted_via' => 'dh_gen_services',
                    ],
                ],
            ]);

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'submitted',
                'from_status' => null,
                'to_status' => 'pending_vp',
                'from_step' => null,
                'to_step' => 'vp_gen_services_change_request_review',
                'from_owner_role' => null,
                'to_owner_role' => 'vp_gen_services',
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => false,
                'is_terminal' => false,
                'remarks' => $validated['form']['reason'],
                'context' => [
                    'review_stage' => 'settings_change_submission',
                    'setting_key' => $validated['form']['setting'],
                    'setting_label' => $this->selectedSettingLabel($validated['form']['setting']),
                    'current_value' => $this->selectedSettingValue($validated['form']['setting']),
                    'proposed_value' => $formattedProposedValue,
                ],
                'acted_at' => now(),
            ]);

            $this->submittedId = $projectRequest->request_number;
        });

        $this->submitted = true;

        $this->dispatch('notify', type: 'info', message: $this->submittedId . ' was submitted and routed to VP Gen Services for review.');
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
        return collect($this->defaultSettingOptions())
            ->map(function (array $option): array {
                $option['value'] = $this->resolveCurrentSettingValue($option['key'], $option['value']);

                return $option;
            })
            ->all();
    }

    protected function defaultSettingOptions(): array
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

    protected function generateRequestNumber(): string
    {
        $year = now()->year;
        $latest = ProjectRequest::query()
            ->where('request_number', 'like', "SCR-{$year}-%")
            ->orderByDesc('request_number')
            ->value('request_number');

        $nextSequence = $latest ? ((int) substr($latest, -3)) + 1 : 1;

        return sprintf('SCR-%s-%03d', $year, $nextSequence);
    }

    protected function selectedSettingLabel(string $key): string
    {
        return collect($this->settingOptions)->firstWhere('key', $key)['label'] ?? $key;
    }

    protected function selectedSettingValue(string $key): ?string
    {
        return collect($this->settingOptions)->firstWhere('key', $key)['value'] ?? null;
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

    public function render()
    {
        return view('livewire.dh-gen-services.settings-change-request-page')
            ->layout('layouts.app', [
                'title' => 'Settings Change Request | EngiStart',
                'header' => 'Settings Change Request',
                'subheader' => 'Submit a system-wide settings change request for VP approval.',
            ]);
    }
}
