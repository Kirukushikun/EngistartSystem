<?php

namespace Tests\Feature;

use App\Livewire\DivisionHead\InboxPage as DivisionHeadInboxPage;
use App\Livewire\FarmManager\NewRequestPage;
use App\Livewire\Shared\RequestSummaryPage;
use App\Models\ProjectRequest;
use App\Models\RequestAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class AssignedEngineerAndJlAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_request_summary_detail_shows_the_assigned_engineer_once_assigned(): void
    {
        $requestor = User::factory()->create(['role' => 'farm_manager', 'is_active' => true]);
        $engineer = User::factory()->create(['role' => 'engineer', 'name' => 'Engr. Dela Cruz', 'is_active' => true]);

        $request = ProjectRequest::create([
            'request_number' => 'APIS-2026-ENGVIS01',
            'requestor_id' => $requestor->id,
            'requestor_role' => 'farm_manager',
            'current_status' => 'accepted',
            'current_step' => 'dh_gen_services_noting',
            'current_owner_role' => 'dh_gen_services',
            'current_owner_id' => null,
            'assigned_engineer_id' => $engineer->id,
            'is_late' => false,
            'is_exception_flow' => false,
            'title' => 'Assigned Engineer Visibility Test',
            'request_type' => 'Production Building',
            'budget_category' => 'small',
            'farm_name' => 'Engineer Visibility Farm',
            'purpose' => 'Testing assigned engineer visibility in summary',
            'description' => 'Assigned engineer visibility test.',
            'submitted_at' => now(),
        ]);

        $this->actingAs($requestor);

        Livewire::test(RequestSummaryPage::class)
            ->assertOk()
            ->assertSee('APIS-2026-ENGVIS01')
            ->assertSee('Engr. Dela Cruz');

        $this->assertSame('Engr. Dela Cruz', $request->fresh()->assignedEngineer->name);
    }

    public function test_jl_submission_with_an_attachment_creates_a_request_attachment_and_is_visible_to_reviewers(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');

        $this->actingAs($farmManager);

        $file = UploadedFile::fake()->create('supporting-doc.pdf', 200, 'application/pdf');

        Livewire::test(NewRequestPage::class)
            ->set('form.title', 'JL With Attachment')
            ->set('form.type', 'others')
            ->set('form.typeOther', 'Custom Type')
            ->set('form.budgetCategory', 'large')
            ->set('timelineAcceptable', 'no')
            ->set('jl.delayReason', 'Site not ready')
            ->set('jl.estimatedTurnoverDate', now()->addDays(120)->toDateString())
            ->set('jl.implicationIfNotCompleted', 'Delayed operations')
            ->set('jl.estimatedFinancialOpportunityLoss', '500000')
            ->set('jlAttachment', $file)
            ->call('openSubmissionReview')
            ->call('submit')
            ->assertSet('submitted', true);

        $request = ProjectRequest::firstOrFail();

        $attachment = RequestAttachment::where('project_request_id', $request->id)->first();
        $this->assertNotNull($attachment, 'Expected a RequestAttachment row to be created for the JL upload.');
        $this->assertSame('jl_supporting_document', $attachment->attachment_type);
        $this->assertSame('supporting-doc.pdf', $attachment->original_name);

        $this->actingAs($divisionHead);

        Livewire::test(DivisionHeadInboxPage::class)
            ->assertOk()
            ->assertSee('supporting-doc.pdf');
    }

    public function test_jl_submission_without_an_attachment_still_succeeds(): void
    {
        $farmManager = $this->makeUser('farm_manager');

        $this->actingAs($farmManager);

        Livewire::test(NewRequestPage::class)
            ->set('form.title', 'JL Without Attachment')
            ->set('form.type', 'others')
            ->set('form.typeOther', 'Custom Type')
            ->set('form.budgetCategory', 'large')
            ->set('timelineAcceptable', 'no')
            ->set('jl.delayReason', 'Site not ready')
            ->set('jl.estimatedTurnoverDate', now()->addDays(120)->toDateString())
            ->set('jl.implicationIfNotCompleted', 'Delayed operations')
            ->set('jl.estimatedFinancialOpportunityLoss', '500000')
            ->call('openSubmissionReview')
            ->call('submit')
            ->assertSet('submitted', true);

        $request = ProjectRequest::firstOrFail();

        $this->assertSame(0, RequestAttachment::where('project_request_id', $request->id)->count());
    }
}
