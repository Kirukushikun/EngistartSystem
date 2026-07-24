<?php

namespace Tests\Feature;

use App\Livewire\DHGenServices\SettingsChangeRequestPage as DhSettingsChangeRequestPage;
use App\Livewire\EDManager\SettingsChangeRequestPage as EdSettingsChangeRequestPage;
use App\Livewire\ITAdmin\SettingsPage;
use App\Models\ProjectRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsChangeRequestRefactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_dh_gen_services_settings_change_submission_routes_to_vp_with_correct_submitted_via(): void
    {
        $dh = User::factory()->create(['role' => 'dh_gen_services', 'is_active' => true]);
        $this->actingAs($dh);

        Livewire::test(DhSettingsChangeRequestPage::class)
            ->set('form.setting', 'lead_time_days')
            ->set('form.newValue', '60')
            ->set('form.reason', 'Testing DH settings change submission after refactor.')
            ->call('openSubmissionReview')
            ->call('submit')
            ->assertSet('submitted', true);

        $request = ProjectRequest::where('request_type', 'Settings Change')->firstOrFail();

        $this->assertSame('pending_vp', $request->current_status);
        $this->assertSame('vp_gen_services', $request->current_owner_role);
        $this->assertSame('dh_gen_services', data_get($request->meta, 'setting_change.submitted_via'));
        $this->assertSame('lead_time_days', data_get($request->meta, 'setting_change.setting_key'));
        $this->assertStringStartsWith('SCR-', $request->request_number);
    }

    public function test_ed_manager_settings_change_submission_routes_to_vp_with_correct_submitted_via(): void
    {
        $ed = User::factory()->create(['role' => 'ed_manager', 'is_active' => true]);
        $this->actingAs($ed);

        Livewire::test(EdSettingsChangeRequestPage::class)
            ->set('form.setting', 'small_threshold')
            ->set('form.newValue', '250000')
            ->set('form.reason', 'Testing ED settings change submission after refactor.')
            ->call('openSubmissionReview')
            ->call('submit')
            ->assertSet('submitted', true);

        $request = ProjectRequest::where('request_type', 'Settings Change')->firstOrFail();

        $this->assertSame('pending_vp', $request->current_status);
        $this->assertSame('vp_gen_services', $request->current_owner_role);
        $this->assertSame('ed_manager', data_get($request->meta, 'setting_change.submitted_via'));
        $this->assertSame('small_threshold', data_get($request->meta, 'setting_change.setting_key'));
        $this->assertStringStartsWith('SCR-', $request->request_number);
    }

    public function test_settings_page_reflects_the_latest_implemented_change(): void
    {
        $itAdmin = User::factory()->create(['role' => 'it_admin', 'is_active' => true]);
        $requestor = User::factory()->create(['role' => 'dh_gen_services', 'is_active' => true]);

        ProjectRequest::create([
            'request_number' => 'SCR-2026-001',
            'requestor_id' => $requestor->id,
            'requestor_role' => 'dh_gen_services',
            'current_status' => 'implemented',
            'current_step' => 'completed',
            'current_owner_role' => null,
            'is_late' => false,
            'is_exception_flow' => false,
            'title' => 'Settings Change: Required Advance Submission (days)',
            'request_type' => 'Settings Change',
            'farm_name' => 'System-wide',
            'purpose' => 'Test',
            'date_needed' => now()->toDateString(),
            'description' => 'Test',
            'submitted_at' => now()->subDays(5),
            'completed_at' => now()->subDay(),
            'last_transitioned_at' => now()->subDay(),
            'meta' => [
                'setting_change' => [
                    'setting_key' => 'lead_time_days',
                    'setting_label' => 'Required Advance Submission (days)',
                    'current_value' => '45 days',
                    'proposed_value' => '60 days',
                    'submitted_via' => 'dh_gen_services',
                ],
            ],
        ]);

        $this->actingAs($itAdmin);

        Livewire::test(SettingsPage::class)
            ->assertOk()
            ->assertSee('60 days');
    }
}
