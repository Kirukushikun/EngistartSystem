<?php

namespace Tests\Feature;

use App\Livewire\Shared\RequestSummaryPage;
use App\Models\ProjectRequest;
use App\Models\RequestAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RequestSummaryDetailViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_table_exposes_crucial_fields_and_view_button_reveals_attachments_and_jl(): void
    {
        $requestor = User::factory()->create(['role' => 'farm_manager', 'name' => 'Jose Santos', 'is_active' => true]);
        $viewer = User::factory()->create(['role' => 'vp_gen_services', 'is_active' => true]);

        $request = ProjectRequest::create([
            'request_number' => 'APIS-2026-SUM01',
            'requestor_id' => $requestor->id,
            'requestor_role' => 'farm_manager',
            'current_status' => 'jl_pending',
            'current_step' => 'vp_gen_services_jl_review',
            'current_owner_role' => 'vp_gen_services',
            'current_owner_id' => null,
            'is_late' => true,
            'is_exception_flow' => true,
            'title' => 'Summary Detail Test Project',
            'request_type' => 'Production Building',
            'budget_category' => 'medium',
            'farm_name' => 'Summary Test Farm',
            'purpose' => 'Testing the summary detail modal',
            'date_needed' => now()->addDays(90),
            'project_start_date' => now()->addDays(45),
            'project_completion_date' => now()->addDays(120),
            'description' => 'Full description shown only in the detail modal.',
            'submitted_at' => now(),
            'meta' => [
                'jl' => [
                    'delayReason' => 'Budget cycle delay',
                    'estimatedTurnoverDate' => now()->addDays(150)->toDateString(),
                    'implicationIfNotCompleted' => 'Feed supply shortfall',
                    'estimatedFinancialOpportunityLoss' => 'PHP 75,000',
                ],
            ],
        ]);

        RequestAttachment::create([
            'project_request_id' => $request->id,
            'uploaded_by_id' => $requestor->id,
            'attachment_type' => 'justification_letter',
            'original_name' => 'jl-summary-test.pdf',
            'disk' => 'public',
            'path' => 'attachments/jl-summary-test.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'is_active' => true,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($viewer);

        Livewire::test(RequestSummaryPage::class)
            ->assertOk()
            // Crucial table columns
            ->assertSee('APIS-2026-SUM01')
            ->assertSee('Summary Test Farm')
            ->assertSee('Jose Santos')
            ->assertSee('Medium (₱200,001 – ₱600,000)')
            // Detail-modal-only content (attachments + JL), present in the rendered HTML behind the hidden modal
            ->assertSee('Budget cycle delay')
            ->assertSee('Feed supply shortfall')
            ->assertSee('jl-summary-test.pdf')
            ->assertSee('Full description shown only in the detail modal.');
    }
}
