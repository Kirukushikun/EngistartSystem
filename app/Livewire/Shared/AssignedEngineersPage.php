<?php

namespace App\Livewire\Shared;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AssignedEngineersPage extends Component
{
    public string $search = '';

    public ?string $formMode = null;

    public ?int $selectedUserId = null;

    public array $form = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public function getEngineersProperty(): Collection
    {
        $items = User::query()
            ->where('role', 'engineer')
            ->withCount([
                'assignedRequests as pending_count' => fn ($query) => $query->where('current_owner_role', 'engineer'),
                'assignedRequests as initialized_count' => fn ($query) => $query->where('current_status', 'initialized'),
            ])
            ->orderBy('name')
            ->get();

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $items = $items->filter(function (User $user) use ($needle): bool {
                return str_contains(mb_strtolower($user->name), $needle)
                    || str_contains(mb_strtolower((string) $user->email), $needle);
            })->values();
        }

        return $items;
    }

    public function createEngineer(): void
    {
        $this->formMode = 'create';
        $this->selectedUserId = null;
        $this->form = [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->resetValidation();
    }

    public function resetPassword(int $userId): void
    {
        $engineer = User::query()->where('role', 'engineer')->find($userId);

        if (! $engineer) {
            return;
        }

        $this->formMode = 'reset';
        $this->selectedUserId = $userId;
        $this->form = [
            'name' => $engineer->name,
            'email' => $engineer->email,
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->resetValidation();
    }

    public function cancelForm(): void
    {
        $this->formMode = null;
        $this->selectedUserId = null;
        $this->resetValidation();
    }

    public function getIsModalOpenProperty(): bool
    {
        return $this->formMode !== null;
    }

    protected function rules(): array
    {
        if ($this->formMode === 'reset') {
            return [
                'form.password' => ['required', 'string', 'min:8', 'confirmed'],
            ];
        }

        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', Rule::unique('users', 'email')],
            'form.password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->formMode === 'create') {
            User::create([
                'name' => $validated['form']['name'],
                'email' => $validated['form']['email'],
                'role' => 'engineer',
                'is_active' => true,
                'password' => Hash::make($validated['form']['password']),
            ]);

            $this->dispatch('notify', type: 'success', message: 'Engineer account created.');
        } elseif ($this->formMode === 'reset' && $this->selectedUserId) {
            $engineer = User::query()->where('role', 'engineer')->find($this->selectedUserId);

            if ($engineer) {
                $engineer->update(['password' => Hash::make($validated['form']['password'])]);
                $this->dispatch('notify', type: 'success', message: 'Engineer password reset.');
            }
        }

        $this->cancelForm();
    }

    public function toggleActive(int $userId): void
    {
        $engineer = User::query()->where('role', 'engineer')->find($userId);

        if (! $engineer) {
            return;
        }

        $engineer->update(['is_active' => ! $engineer->is_active]);
    }

    public function render()
    {
        return view('livewire.shared.assigned-engineers-page')
            ->layout('layouts.app', [
                'title' => 'Assigned Engineers | EngiStart',
                'header' => 'Assigned Engineers',
                'subheader' => 'Create and manage local login credentials for Engineer 1/2/3, independent of the external directory.',
            ]);
    }
}
