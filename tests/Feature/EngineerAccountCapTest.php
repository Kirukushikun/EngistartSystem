<?php

namespace Tests\Feature;

use App\Livewire\ITAdmin\UsersPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EngineerAccountCapTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role, bool $active = true): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => $active]);
    }

    public function test_a_fifth_engineer_cannot_be_activated_while_four_are_already_active(): void
    {
        $itAdmin = $this->makeUser('it_admin');

        for ($i = 0; $i < 4; $i++) {
            $this->makeUser('engineer', true);
        }

        $disabledEngineer = $this->makeUser('engineer', false);

        $this->actingAs($itAdmin);

        Livewire::test(UsersPage::class)
            ->call('toggleAccess', $disabledEngineer->id);

        $disabledEngineer->refresh();

        $this->assertFalse($disabledEngineer->is_active, 'A 5th engineer must not be activated while 4 are already active.');
        $this->assertSame(4, User::where('role', 'engineer')->where('is_active', true)->count());
    }

    public function test_disabling_an_engineer_frees_a_slot_for_the_next_activation(): void
    {
        $itAdmin = $this->makeUser('it_admin');

        $engineers = collect();
        for ($i = 0; $i < 4; $i++) {
            $engineers->push($this->makeUser('engineer', true));
        }

        $disabledEngineer = $this->makeUser('engineer', false);

        $this->actingAs($itAdmin);

        $component = Livewire::test(UsersPage::class)
            ->call('toggleAccess', $engineers->first()->id);

        $engineers->first()->refresh();
        $this->assertFalse($engineers->first()->is_active);

        $component->call('toggleAccess', $disabledEngineer->id);

        $disabledEngineer->refresh();
        $this->assertTrue($disabledEngineer->is_active, 'Disabling an engineer should free a slot for the next activation.');
        $this->assertSame(4, User::where('role', 'engineer')->where('is_active', true)->count());
    }

    public function test_toggling_a_non_engineer_role_is_never_capped(): void
    {
        $itAdmin = $this->makeUser('it_admin');

        for ($i = 0; $i < 4; $i++) {
            $this->makeUser('engineer', true);
        }

        $farmManager = $this->makeUser('farm_manager', false);

        $this->actingAs($itAdmin);

        Livewire::test(UsersPage::class)
            ->call('toggleAccess', $farmManager->id);

        $farmManager->refresh();

        $this->assertTrue($farmManager->is_active);
    }
}
