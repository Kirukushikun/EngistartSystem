<?php

namespace Tests\Feature;

use App\Livewire\FarmManager\NewRequestPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The memo header on the new-request form used to be hardcoded to the seeded
 * demo accounts, so every farm manager saw "Jose Santos / Farm A - Bamban,
 * Tarlac / Div. Head Santos" regardless of who was logged in.
 *
 * "TO" is scoped to the requestor's own department: name that department's
 * division head, or say "Division Head" when there isn't exactly one.
 */
class NewRequestMemoHeaderTest extends TestCase
{
    use RefreshDatabase;

    protected function farmManager(string $department = 'Poultry'): User
    {
        return User::factory()->create([
            'name' => 'Maria Cruz',
            'role' => 'farm_manager',
            'farm' => 'BROOKDALE',
            'department' => $department,
            'is_active' => true,
        ]);
    }

    protected function divisionHead(string $name, ?string $department, bool $active = true): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => 'division_head',
            'department' => $department,
            'is_active' => $active,
        ]);
    }

    public function test_header_shows_the_signed_in_farm_manager_not_a_hardcoded_name(): void
    {
        $this->divisionHead('Div. Head Santos', 'Poultry');

        Livewire::actingAs($this->farmManager())
            ->test(NewRequestPage::class)
            ->assertSee('Maria Cruz')
            ->assertSee('BROOKDALE')
            ->assertDontSee('Jose Santos')
            ->assertDontSee('Farm A – Bamban, Tarlac');
    }

    public function test_names_the_division_head_of_the_requestors_own_department(): void
    {
        $this->divisionHead('Poultry Head', 'Poultry');
        $this->divisionHead('Swine Head', 'Swine');

        Livewire::actingAs($this->farmManager('Poultry'))
            ->test(NewRequestPage::class)
            ->assertSee('Poultry Head')
            ->assertDontSee('Swine Head');
    }

    public function test_falls_back_to_role_label_when_that_department_has_no_division_head(): void
    {
        // A division head exists, but for a different department.
        $this->divisionHead('Swine Head', 'Swine');

        Livewire::actingAs($this->farmManager('Poultry'))
            ->test(NewRequestPage::class)
            ->assertSee('Division Head')
            ->assertDontSee('Swine Head');
    }

    public function test_falls_back_when_the_department_has_more_than_one_division_head(): void
    {
        $this->divisionHead('Head One', 'Poultry');
        $this->divisionHead('Head Two', 'Poultry');

        Livewire::actingAs($this->farmManager('Poultry'))
            ->test(NewRequestPage::class)
            ->assertSee('Division Head')
            ->assertDontSee('Head One')
            ->assertDontSee('Head Two');
    }

    public function test_ignores_a_disabled_division_head(): void
    {
        $this->divisionHead('Retired Head', 'Poultry', active: false);

        Livewire::actingAs($this->farmManager('Poultry'))
            ->test(NewRequestPage::class)
            ->assertSee('Division Head')
            ->assertDontSee('Retired Head');
    }

    public function test_falls_back_when_the_requestor_has_no_department(): void
    {
        $this->divisionHead('Poultry Head', 'Poultry');

        Livewire::actingAs($this->farmManager(department: ''))
            ->test(NewRequestPage::class)
            ->assertSuccessful()
            ->assertSee('Division Head')
            ->assertDontSee('Poultry Head');
    }

    public function test_header_does_not_break_when_no_division_head_exists_at_all(): void
    {
        Livewire::actingAs($this->farmManager('Hatchery'))
            ->test(NewRequestPage::class)
            ->assertSuccessful()
            ->assertSee('Division Head')
            ->assertSee('Maria Cruz');
    }
}
