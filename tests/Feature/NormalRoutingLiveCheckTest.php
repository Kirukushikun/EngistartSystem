<?php

namespace Tests\Feature;

use App\Livewire\DivisionHead\InboxPage as DivisionHeadInboxPage;
use App\Livewire\FarmManager\NewRequestPage;
use App\Models\ProjectRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NormalRoutingLiveCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_normal_yes_path_request_lands_actionable_in_division_heads_inbox_in_one_step(): void
    {
        $farmManager = User::factory()->create(['role' => 'farm_manager', 'is_active' => true]);
        $divisionHead = User::factory()->create(['role' => 'division_head', 'is_active' => true]);
        $otherDivisionHead = User::factory()->create(['role' => 'division_head', 'is_active' => true]);

        $this->actingAs($farmManager);

        // Normal submission (timelineAcceptable = yes -> not a JL/exception request) now
        // includes the meeting date/time in the same form -- no second step required.
        Livewire::test(NewRequestPage::class)
            ->set('form.title', 'Normal Routing Check')
            ->set('form.type', 'production_building')
            ->set('form.needed', now()->addDays(60)->toDateString())
            ->set('form.budgetCategory', 'small')
            ->set('form.mtgDate', now()->addDays(10)->toDateString())
            ->set('form.mtgTime', '10:00')
            ->set('timelineAcceptable', 'yes')
            ->call('openSubmissionReview')
            ->call('submit')
            ->assertSet('submitted', true);

        $request = ProjectRequest::firstOrFail();

        // It lands directly with Division Head in one step -- no intermediate
        // "awaiting assessment meeting" state with the Farm Manager anymore.
        $this->assertSame('division_head', $request->current_owner_role);
        $this->assertNull($request->current_owner_id);
        $this->assertSame('division_head_review', $request->current_step);
        $this->assertNotNull($request->preferred_meeting_date);
        $this->assertSame('10:00', (string) $request->preferred_meeting_time);

        // Confirm it's actually visible and actionable in every Division Head's inbox
        // (role-wide inbox, since current_owner_id is null at this stage).
        $this->actingAs($divisionHead);
        Livewire::test(DivisionHeadInboxPage::class)
            ->assertOk()
            ->assertSee($request->request_number)
            ->assertSee('Normal Routing Check');

        $rows = Livewire::test(DivisionHeadInboxPage::class)->get('inboxItems');
        $row = collect($rows)->firstWhere('id', $request->request_number);
        $this->assertNotNull($row, 'Request not found in Division Head inbox items.');
        $this->assertTrue($row['isPendingHere'], 'Request should be actionable (isPendingHere) for Division Head.');

        // A second Division Head account sees it too (role-wide inbox, not tied to one person).
        $this->actingAs($otherDivisionHead);
        Livewire::test(DivisionHeadInboxPage::class)
            ->assertOk()
            ->assertSee($request->request_number);
    }
}
