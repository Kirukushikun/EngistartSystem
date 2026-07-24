<?php

namespace Tests\Feature;

use App\Livewire\DHGenServices\NotingPage as DhNotingPage;
use App\Livewire\DivisionHead\InboxPage as DivisionHeadInboxPage;
use App\Livewire\Engineer\InboxPage as EngineerInboxPage;
use App\Livewire\EDManager\InboxPage as EdManagerInboxPage;
use App\Livewire\FarmManager\NewRequestPage;
use App\Livewire\VPGenServices\InboxPage as VpInboxPage;
use App\Models\ProjectRequest;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class WorkflowSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    protected function submitNormalRequest(string $title): Testable
    {
        return Livewire::test(NewRequestPage::class)
            ->set('form.title', $title)
            ->set('form.type', 'production_building')
            ->set('form.needed', now()->addDays(60)->toDateString())
            ->set('form.budgetCategory', 'small')
            ->set('form.mtgDate', now()->addDays(10)->toDateString())
            ->set('form.mtgTime', '10:00')
            ->set('timelineAcceptable', 'yes')
            ->call('openSubmissionReview')
            ->call('submit');
    }

    public function test_yes_path_full_chain_to_engineer_initialization(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $vp = $this->makeUser('vp_gen_services');
        $ed = $this->makeUser('ed_manager');
        $dh = $this->makeUser('dh_gen_services');
        $engineer = $this->makeUser('engineer');

        $this->actingAs($farmManager);

        $this->submitNormalRequest('Test Project Description')
            ->assertSet('submitted', true);

        $request = ProjectRequest::firstOrFail();
        $this->assertSame('submitted', $request->current_status);
        $this->assertSame('division_head_review', $request->current_step);
        $this->assertSame('division_head', $request->current_owner_role);
        $this->assertNull($request->current_owner_id);
        $this->assertNotNull($request->project_start_date);
        $this->assertNotNull($request->project_completion_date);
        $this->assertNotNull($request->preferred_meeting_date);
        $this->assertSame(
            now()->addDays(30)->toDateString(),
            $request->project_start_date->toDateString()
        );
        $this->assertSame(
            now()->addDays(30 + 45)->toDateString(),
            $request->project_completion_date->toDateString()
        );

        $this->actingAs($divisionHead);
        Livewire::test(DivisionHeadInboxPage::class)
            ->call('recommend', ['requestId' => $request->request_number]);

        $request->refresh();
        $this->assertSame('vp_gen_services_approval', $request->current_step);
        $this->assertSame('vp_gen_services', $request->current_owner_role);

        $this->actingAs($vp);
        Livewire::test(VpInboxPage::class)
            ->call('approve', ['requestId' => $request->request_number]);

        $request->refresh();
        $this->assertSame('ed_manager_acceptance', $request->current_step);
        $this->assertSame('ed_manager', $request->current_owner_role);

        $this->actingAs($ed);
        Livewire::test(EdManagerInboxPage::class)
            ->set("selectedEngineer.{$request->request_number}", $engineer->id)
            ->call('accept', ['requestId' => $request->request_number]);

        $request->refresh();
        $this->assertSame('accepted', $request->current_status);
        $this->assertSame('dh_gen_services_noting', $request->current_step);
        $this->assertSame('dh_gen_services', $request->current_owner_role);
        $this->assertSame($engineer->id, $request->assigned_engineer_id);

        $this->actingAs($dh);
        Livewire::test(DhNotingPage::class)
            ->call('noteForward', ['requestId' => $request->request_number]);

        $request->refresh();
        $this->assertSame('noted', $request->current_status);
        $this->assertSame('engineer_initialization', $request->current_step);
        $this->assertSame('engineer', $request->current_owner_role);
        $this->assertSame($engineer->id, $request->current_owner_id);
        $this->assertSame($engineer->id, $request->assigned_engineer_id);

        $this->actingAs($engineer);
        Livewire::test(EngineerInboxPage::class)
            ->call('markInitialized', ['requestId' => $request->request_number]);

        $request->refresh();
        $this->assertSame('initialized', $request->current_status);
        $this->assertNull($request->current_owner_role);
        $this->assertNotNull($request->completed_at);
    }

    public function test_jl_no_path_skips_division_head_and_vp_after_dual_approval(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $vp = $this->makeUser('vp_gen_services');

        $this->actingAs($farmManager);

        Livewire::test(NewRequestPage::class)
            ->set('form.title', 'Late Project')
            ->set('form.type', 'others')
            ->set('form.typeOther', 'Custom Type')
            ->set('form.needed', now()->addDays(20)->toDateString())
            ->set('form.budgetCategory', 'large')
            ->set('form.mtgDate', now()->addDays(5)->toDateString())
            ->set('form.mtgTime', '14:00')
            ->set('timelineAcceptable', 'no')
            ->set('jl.delayReason', 'Site not ready')
            ->set('jl.estimatedTurnoverDate', now()->addDays(120)->toDateString())
            ->set('jl.implicationIfNotCompleted', 'Delayed operations')
            ->set('jl.estimatedFinancialOpportunityLoss', '500000')
            ->call('openSubmissionReview')
            ->call('submit')
            ->assertSet('submitted', true);

        $request = ProjectRequest::firstOrFail();
        $this->assertSame('jl_pending', $request->current_status);
        $this->assertSame('division_head_jl_review', $request->current_step);
        $this->assertTrue($request->is_exception_flow);
        $this->assertSame('Custom Type', $request->request_type);

        $this->actingAs($divisionHead);
        Livewire::test(DivisionHeadInboxPage::class)
            ->call('recommend', ['requestId' => $request->request_number]);

        $request->refresh();
        $this->assertSame('jl_pending', $request->current_status);
        $this->assertSame('vp_gen_services_jl_review', $request->current_step);
        $this->assertSame('vp_gen_services', $request->current_owner_role);

        $this->actingAs($vp);
        Livewire::test(VpInboxPage::class)
            ->call('approve', ['requestId' => $request->request_number]);

        $request->refresh();
        // JL path must skip Division Head and VP Gen Services the second time around
        // and go straight to ED Manager, since the meeting date was already collected at submission.
        $this->assertSame('jl_approved', $request->current_status);
        $this->assertSame('ed_manager_acceptance', $request->current_step);
        $this->assertSame('ed_manager', $request->current_owner_role);
    }

    public function test_recommend_notifies_vp_gen_services_but_not_the_acting_division_head(): void
    {
        Notification::fake();

        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $vp = $this->makeUser('vp_gen_services');
        $otherDivisionHead = $this->makeUser('division_head');

        $this->actingAs($farmManager);
        $this->submitNormalRequest('Notify Test Project');

        $request = ProjectRequest::firstOrFail();

        $this->actingAs($divisionHead);
        Livewire::test(DivisionHeadInboxPage::class)
            ->call('recommend', ['requestId' => $request->request_number]);

        Notification::assertSentTo($vp, WorkflowNotification::class, function (WorkflowNotification $notification) use ($request) {
            return $notification->request->is($request) && $notification->event === 'recommended';
        });

        // The whole vp_gen_services role inbox gets notified for this event, but the Division Head who just
        // acted must not notify themselves, and other Division Heads shouldn't get a "recommended" notice
        // (they legitimately received the earlier "submitted" notice as part of the role-wide inbox).
        $recommendedEvent = fn (WorkflowNotification $notification) => $notification->event === 'recommended';
        Notification::assertNotSentTo($divisionHead, WorkflowNotification::class, $recommendedEvent);
        Notification::assertNotSentTo($otherDivisionHead, WorkflowNotification::class, $recommendedEvent);
    }

    public function test_engineer_initialization_notifies_the_original_requestor(): void
    {
        Notification::fake();

        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $vp = $this->makeUser('vp_gen_services');
        $ed = $this->makeUser('ed_manager');
        $dh = $this->makeUser('dh_gen_services');
        $engineer = $this->makeUser('engineer');

        $this->actingAs($farmManager);
        $this->submitNormalRequest('Initialization Notify Test');

        $request = ProjectRequest::firstOrFail();

        $this->actingAs($divisionHead);
        Livewire::test(DivisionHeadInboxPage::class)->call('recommend', ['requestId' => $request->request_number]);

        $this->actingAs($vp);
        Livewire::test(VpInboxPage::class)->call('approve', ['requestId' => $request->request_number]);

        $this->actingAs($ed);
        Livewire::test(EdManagerInboxPage::class)
            ->set("selectedEngineer.{$request->request_number}", $engineer->id)
            ->call('accept', ['requestId' => $request->request_number]);

        $this->actingAs($dh);
        Livewire::test(DhNotingPage::class)
            ->call('noteForward', ['requestId' => $request->request_number]);

        $this->actingAs($engineer);
        Livewire::test(EngineerInboxPage::class)->call('markInitialized', ['requestId' => $request->request_number]);

        Notification::assertSentTo($farmManager, WorkflowNotification::class, function (WorkflowNotification $notification) use ($request) {
            return $notification->request->is($request) && $notification->event === 'initialized';
        });
    }
}
