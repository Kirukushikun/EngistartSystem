<?php

namespace App\Livewire\Shared;

use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Models\User;
use App\Support\WorkflowNotifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
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

    /** The pending requests blocking deletion of $selectedUserId, when formMode === 'reassign'. */
    public array $pendingRequests = [];

    /** request_number => target engineer id picked in the reassignment panel. */
    public array $reassignPicks = [];

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

    public function editEngineer(int $userId): void
    {
        $engineer = User::query()->where('role', 'engineer')->find($userId);

        if (! $engineer) {
            return;
        }

        $this->formMode = 'edit';
        $this->selectedUserId = $userId;
        $this->form = [
            'name' => $engineer->name,
            'email' => $engineer->email,
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
        $this->pendingRequests = [];
        $this->reassignPicks = [];
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

        if ($this->formMode === 'edit') {
            return [
                'form.name' => ['required', 'string', 'max:255'],
                'form.email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->selectedUserId)],
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
            $atCap = $this->activeEngineerCount() >= 4;

            User::create([
                'name' => $validated['form']['name'],
                'email' => $validated['form']['email'],
                'role' => 'engineer',
                'is_active' => ! $atCap,
                'password' => Hash::make($validated['form']['password']),
            ]);

            $this->dispatch('notify', type: $atCap ? 'warn' : 'success', message: $atCap
                ? '4 engineer accounts are already active — this one was added disabled. Disable another engineer to activate it.'
                : 'Engineer account created.');
        } elseif ($this->formMode === 'reset' && $this->selectedUserId) {
            $engineer = User::query()->where('role', 'engineer')->find($this->selectedUserId);

            if ($engineer) {
                $engineer->update(['password' => Hash::make($validated['form']['password'])]);
                $this->dispatch('notify', type: 'success', message: 'Engineer password reset.');
            }
        } elseif ($this->formMode === 'edit' && $this->selectedUserId) {
            $engineer = User::query()->where('role', 'engineer')->find($this->selectedUserId);

            if ($engineer) {
                $engineer->update([
                    'name' => $validated['form']['name'],
                    'email' => $validated['form']['email'],
                ]);
                $this->dispatch('notify', type: 'success', message: 'Engineer account updated.');
            }
        }

        $this->cancelForm();
    }

    protected function activeEngineerCount(): int
    {
        return User::query()->where('role', 'engineer')->where('is_active', true)->count();
    }

    /**
     * Opens the confirm dialog, or refuses outright with a clear reason --
     * never a silent no-op on a destructive action.
     */
    public function confirmDeleteEngineer(int $userId): void
    {
        $engineer = User::query()
            ->where('role', 'engineer')
            ->withCount([
                'assignedRequests as pending_count' => fn ($query) => $query->where('current_owner_role', 'engineer'),
                'assignedRequests as initialized_count' => fn ($query) => $query->where('current_status', 'initialized'),
            ])
            ->find($userId);

        if (! $engineer) {
            return;
        }

        if ($engineer->id === Auth::id()) {
            $this->dispatch('notify', type: 'danger', message: 'You cannot delete your own account.');

            return;
        }

        if ($engineer->pending_count > 0) {
            $this->openReassignmentPanel($userId);

            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Delete engineer account?',
            'message' => 'This permanently removes their local login. Past project history stays intact but will no longer show them as the assigned engineer. This cannot be undone.',
            'tone' => 'danger',
            'confirmText' => 'Delete account',
            'confirmEvent' => 'engineerDeleteConfirmed',
            'confirmTarget' => self::class,
            'summary' => [
                ['label' => 'Name', 'value' => $engineer->name],
                ['label' => 'Email', 'value' => $engineer->email],
                ['label' => 'Initialized projects on record', 'value' => (string) $engineer->initialized_count],
            ],
            'payload' => ['userId' => $userId],
        ])->to(ConfirmationModal::class);
    }

    #[On('engineerDeleteConfirmed')]
    public function deleteEngineer(array $payload): void
    {
        $userId = (int) ($payload['userId'] ?? 0);

        $engineer = User::query()
            ->where('role', 'engineer')
            ->withCount(['assignedRequests as pending_count' => fn ($query) => $query->where('current_owner_role', 'engineer')])
            ->find($userId);

        if (! $engineer) {
            return;
        }

        // Re-check guardrails: state may have changed between opening the
        // dialog and confirming it.
        if ($engineer->id === Auth::id() || $engineer->pending_count > 0) {
            $this->dispatch('notify', type: 'danger', message: 'That account can no longer be deleted -- refresh and try again.');

            return;
        }

        $name = $engineer->name;
        $engineer->delete();

        $this->dispatch('notify', type: 'success', message: "{$name}'s account was deleted.");
    }

    /**
     * Lists the specific requests keeping delete blocked, and lets the admin
     * clear each one individually -- reassign it to another engineer, or send
     * it back to ED Manager to pick a new one -- rather than being stuck with
     * a flat refusal and no way forward.
     */
    protected function openReassignmentPanel(int $userId): void
    {
        $this->formMode = 'reassign';
        $this->selectedUserId = $userId;
        $this->loadPendingRequests($userId);
        $this->resetValidation();
    }

    protected function loadPendingRequests(int $userId): void
    {
        $this->pendingRequests = ProjectRequest::query()
            ->where('current_owner_role', 'engineer')
            ->where('current_owner_id', $userId)
            ->whereNull('withdrawn_at')
            ->orderBy('submitted_at')
            ->get(['request_number', 'title', 'farm_name'])
            ->map(fn (ProjectRequest $request) => [
                'request_number' => $request->request_number,
                'title' => $request->title,
                'farm_name' => $request->farm_name,
            ])
            ->all();

        $this->reassignPicks = collect($this->pendingRequests)
            ->mapWithKeys(fn (array $request) => [$request['request_number'] => ''])
            ->all();
    }

    /**
     * Other active engineers to reassign to -- never the engineer being
     * removed, never a disabled one (they can't act on it either).
     */
    public function getReassignEngineerOptionsProperty(): Collection
    {
        return User::query()
            ->where('role', 'engineer')
            ->where('is_active', true)
            ->where('id', '!=', $this->selectedUserId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function reassignRequest(string $requestNumber): void
    {
        $targetId = (int) ($this->reassignPicks[$requestNumber] ?? 0);

        if (! $targetId) {
            $this->dispatch('notify', type: 'warn', message: 'Select an engineer to reassign this request to.');

            return;
        }

        $target = User::query()->where('role', 'engineer')->where('is_active', true)->find($targetId);

        if (! $target) {
            $this->dispatch('notify', type: 'danger', message: 'That engineer is no longer available. Refresh and try again.');

            return;
        }

        $actor = Auth::user();

        $request = DB::transaction(function () use ($requestNumber, $target, $actor) {
            $request = ProjectRequest::query()
                ->where('request_number', $requestNumber)
                ->where('current_owner_role', 'engineer')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousOwnerId = $request->current_owner_id;

            $request->update([
                'current_owner_id' => $target->id,
                'assigned_engineer_id' => $target->id,
                'last_transitioned_at' => now(),
            ]);

            RequestTransition::create([
                'project_request_id' => $request->id,
                'acted_by_id' => $actor?->id,
                'acted_by_role' => $actor?->role,
                'action' => 'engineer_reassigned',
                'from_status' => $request->current_status,
                'to_status' => $request->current_status,
                'from_step' => $request->current_step,
                'to_step' => $request->current_step,
                'from_owner_role' => 'engineer',
                'to_owner_role' => 'engineer',
                'to_owner_id' => $target->id,
                'is_rework' => false,
                'is_exception_path' => $request->is_late,
                'is_terminal' => false,
                'remarks' => "Reassigned from a removed engineer account to {$target->name}.",
                'context' => ['previous_engineer_id' => $previousOwnerId, 'new_engineer_id' => $target->id],
                'acted_at' => now(),
            ]);

            return $request;
        });

        WorkflowNotifier::notifyOwner(
            $request,
            'engineer_reassigned',
            'Project Reassigned to You',
            "{$requestNumber} — {$request->title} was reassigned to you for initialization."
        );

        $this->dispatch('notify', type: 'success', message: "{$requestNumber} reassigned to {$target->name}.");

        $this->loadPendingRequests((int) $this->selectedUserId);
    }

    /**
     * No replacement engineer on hand -- send it back to ED Manager to pick
     * one fresh, exactly the state a request is in right after VP approval,
     * before ED Manager ever assigns an engineer.
     */
    public function removeAssignment(string $requestNumber): void
    {
        $actor = Auth::user();

        $request = DB::transaction(function () use ($requestNumber, $actor) {
            $request = ProjectRequest::query()
                ->where('request_number', $requestNumber)
                ->where('current_owner_role', 'engineer')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $request->current_status;
            $previousStep = $request->current_step;

            $request->update([
                'current_status' => 'vp_approved',
                'current_step' => 'ed_manager_acceptance',
                'current_owner_role' => 'ed_manager',
                'current_owner_id' => null,
                'assigned_engineer_id' => null,
                'last_transitioned_at' => now(),
            ]);

            RequestTransition::create([
                'project_request_id' => $request->id,
                'acted_by_id' => $actor?->id,
                'acted_by_role' => $actor?->role,
                'action' => 'engineer_assignment_removed',
                'from_status' => $previousStatus,
                'to_status' => 'vp_approved',
                'from_step' => $previousStep,
                'to_step' => 'ed_manager_acceptance',
                'from_owner_role' => 'engineer',
                'to_owner_role' => 'ed_manager',
                'to_owner_id' => null,
                'is_rework' => true,
                'is_exception_path' => $request->is_late,
                'is_terminal' => false,
                'remarks' => 'Engineer assignment removed; returned to ED Manager to assign a new engineer.',
                'context' => [],
                'acted_at' => now(),
            ]);

            return $request;
        });

        WorkflowNotifier::notifyOwner(
            $request,
            'engineer_assignment_removed',
            'Engineer Needed',
            "{$requestNumber} — {$request->title} needs a new engineer assigned."
        );

        $this->dispatch('notify', type: 'warn', message: "{$requestNumber} sent back to ED Manager to assign a new engineer.");

        $this->loadPendingRequests((int) $this->selectedUserId);
    }

    public function toggleActive(int $userId): void
    {
        $engineer = User::query()->where('role', 'engineer')->find($userId);

        if (! $engineer) {
            return;
        }

        if (! $engineer->is_active && $this->activeEngineerCount() >= 4) {
            $this->dispatch('notify', type: 'warn', message: 'Only 4 engineer accounts can be active at a time. Disable another engineer first.');

            return;
        }

        $engineer->update(['is_active' => ! $engineer->is_active]);
    }

    public function render()
    {
        return view('livewire.shared.assigned-engineers-page')
            ->layout('layouts.app', [
                'title' => 'Assigned Engineers | Project Initialization System',
                'header' => 'Assigned Engineers',
                'subheader' => 'Create and manage local login credentials for Engineer 1/2/3, independent of the external directory.',
            ]);
    }
}
