<?php

namespace Tests\Feature;

use App\Livewire\FarmManager\MyRequestsPage;
use App\Models\ProjectRequest;
use App\Models\RequestAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyRequestsDetailViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_farm_manager_sees_full_detail_including_budget_timeline_jl_and_attachments_for_their_own_request(): void
    {
        $farmManager = User::factory()->create(['role' => 'farm_manager', 'is_active' => true]);

        $request = ProjectRequest::create([
            'request_number' => 'APIS-2026-MINE01',
            'requestor_id' => $farmManager->id,
            'requestor_role' => 'farm_manager',
            'current_status' => 'jl_pending',
            'current_step' => 'division_head_jl_review',
            'current_owner_role' => 'division_head',
            'current_owner_id' => null,
            'is_late' => true,
            'is_exception_flow' => true,
            'title' => 'My Own Request Detail Test',
            'request_type' => 'Production Building',
            'budget_category' => 'large',
            'farm_name' => 'My Farm',
            'purpose' => 'Testing my-requests detail view',
            'date_needed' => now()->addDays(90),
            'project_start_date' => now()->addDays(45),
            'project_completion_date' => now()->addDays(150),
            'description' => 'Full description only visible in the detailed view.',
            'submitted_at' => now(),
            'meta' => [
                'jl' => [
                    'delayReason' => 'Waiting on permits',
                    'estimatedTurnoverDate' => now()->addDays(200)->toDateString(),
                    'implicationIfNotCompleted' => 'Operational delay',
                    'estimatedFinancialOpportunityLoss' => 'PHP 120,000',
                ],
            ],
        ]);

        RequestAttachment::create([
            'project_request_id' => $request->id,
            'uploaded_by_id' => $farmManager->id,
            'attachment_type' => 'justification_letter',
            'original_name' => 'my-jl.pdf',
            'disk' => 'public',
            'path' => 'attachments/my-jl.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
            'is_active' => true,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($farmManager);

        Livewire::test(MyRequestsPage::class)
            ->assertOk()
            ->assertSee('APIS-2026-MINE01')
            ->assertSee('Full description only visible in the detailed view.')
            ->assertSee('Large (above ₱600,000)')
            ->assertSee('Waiting on permits')
            ->assertSee('Operational delay')
            ->assertSee('my-jl.pdf');
    }
}
