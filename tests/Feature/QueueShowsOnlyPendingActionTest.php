<?php

namespace Tests\Feature;

use App\Livewire\DHGenServices\NotingPage as DhNotingPage;
use App\Livewire\DivisionHead\InboxPage as DivisionHeadInboxPage;
use App\Livewire\Engineer\InboxPage as EngineerInboxPage;
use App\Livewire\EDManager\InboxPage as EdManagerInboxPage;
use App\Livewire\FarmManager\MyRequestsPage;
use App\Livewire\FarmManager\NewRequestPage;
use App\Livewire\VPGenServices\InboxPage as VpInboxPage;
use App\Models\ProjectRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QueueShowsOnlyPendingActionTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_each_reviewer_queue_drops_a_request_the_moment_they_act_on_it(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $vp = $this->makeUser('vp_gen_services');
        $ed = $this->makeUser('ed_manager');
        $dh = $this->makeUser('dh_gen_services');
        $engineer = $this->makeUser('engineer');

        $this->actingAs($farmManager);
        Livewire::test(NewRequestPage::class)
            ->set('form.title', 'Queue Visibility Test')
            ->set('form.type', 'production_building')
            ->set('form.dateSubmitted', now()->addDays(60)->toDateString())
            ->set('form.budgetCategory', 'small')
            ->set('form.mtgDate', now()->addDays(10)->toDateString())
            ->set('form.mtgTime', '10:00')
            ->set('timelineAcceptable', 'yes')
            ->call('openSubmissionReview')
            ->call('submit');

        $request = ProjectRequest::firstOrFail();

        // Division Head: visible before acting, gone from the queue right after.
        $this->actingAs($divisionHead);
        $this->assertTrue($this->queueContains(DivisionHeadInboxPage::class, 'inboxItems', $request->request_number));

        Livewire::test(DivisionHeadInboxPage::class)->call('recommend', ['requestId' => $request->request_number]);

        $this->assertFalse($this->queueContains(DivisionHeadInboxPage::class, 'inboxItems', $request->request_number));

        // VP: now visible, gone right after approving.
        $this->actingAs($vp);
        $this->assertTrue($this->queueContains(VpInboxPage::class, 'inboxItems', $request->request_number));

        Livewire::test(VpInboxPage::class)->call('approve', ['requestId' => $request->request_number]);

        $this->assertFalse($this->queueContains(VpInboxPage::class, 'inboxItems', $request->request_number));

        // ED Manager: now visible, gone right after accepting (+ assigning the engineer).
        $this->actingAs($ed);
        $this->assertTrue($this->queueContains(EdManagerInboxPage::class, 'inboxItems', $request->request_number));

        Livewire::test(EdManagerInboxPage::class)
            ->set("selectedEngineer.{$request->request_number}", $engineer->id)
            ->call('accept', ['requestId' => $request->request_number]);

        $this->assertFalse($this->queueContains(EdManagerInboxPage::class, 'inboxItems', $request->request_number));

        // DH Gen Services: now visible, gone right after noting/forwarding.
        $this->actingAs($dh);
        $this->assertTrue($this->queueContains(DhNotingPage::class, 'items', $request->request_number));

        Livewire::test(DhNotingPage::class)->call('noteForward', ['requestId' => $request->request_number]);

        $this->assertFalse($this->queueContains(DhNotingPage::class, 'items', $request->request_number));

        // Engineer: now visible, gone right after marking initialized.
        $this->actingAs($engineer);
        $this->assertTrue($this->queueContains(EngineerInboxPage::class, 'items', $request->request_number));

        Livewire::test(EngineerInboxPage::class)->call('markInitialized', ['requestId' => $request->request_number]);

        $this->assertFalse($this->queueContains(EngineerInboxPage::class, 'items', $request->request_number));

        // But the Farm Manager's own "My Requests" history still shows it throughout -- that page
        // was explicitly excluded from this change since it's the requestor's own record, not a queue.
        $this->actingAs($farmManager);
        $this->assertTrue($this->queueContains(MyRequestsPage::class, 'requests', $request->request_number));
    }

    protected function queueContains(string $component, string $property, string $requestNumber): bool
    {
        $items = Livewire::test($component)->get($property);

        return collect($items)->contains(fn (array $item) => $item['id'] === $requestNumber);
    }
}
