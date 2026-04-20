<?php

namespace App\Livewire\FarmManager;

use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class MyRequestsPage extends Component
{
    public string $filter = 'all';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    protected function loadRequests(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return ProjectRequest::query()
            ->where('requestor_id', $user->id)
            ->latest('submitted_at')
            ->get()
            ->map(fn (ProjectRequest $request): array => $this->mapRequestRecord($request))
            ->values();
    }

    protected function mapRequestRecord(ProjectRequest $request): array
    {
        return [
            'dbId' => $request->id,
            'id' => $request->request_number,
            'title' => $request->title,
            'needed' => optional($request->date_needed)->toDateString(),
            'submitted' => optional($request->submitted_at ?? $request->created_at)->toDateString(),
            'status' => $request->current_status,
            'isLate' => $request->is_late,
            'isEditable' => $request->isEditableByRequestor(),
            'isWithdrawn' => $request->withdrawn_at !== null,
            'chain' => $this->buildChain($request),
        ];
    }

    public function confirmWithdraw(int $requestId): void
    {
        $request = ProjectRequest::query()
            ->whereKey($requestId)
            ->where('requestor_id', Auth::id())
            ->whereNull('first_reviewed_at')
            ->whereNull('locked_at')
            ->whereNull('withdrawn_at')
            ->first();

        if (! $request) {
            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Withdraw request?',
            'message' => 'This request has not been picked up by a reviewer yet. You can withdraw it now and submit a corrected one later.',
            'tone' => 'danger',
            'confirmText' => 'Withdraw request',
            'confirmEvent' => 'requestWithdrawConfirmed',
            'confirmTarget' => self::class,
            'summary' => [
                ['label' => 'Request ID', 'value' => $request->request_number],
                ['label' => 'Project Title', 'value' => $request->title],
                ['label' => 'Current Status', 'value' => str_replace('_', ' ', $request->current_status)],
            ],
            'payload' => ['requestId' => $request->id],
        ])->to(ConfirmationModal::class);
    }

    #[On('requestWithdrawConfirmed')]
    public function withdraw(array $payload): void
    {
        $requestId = (int) ($payload['requestId'] ?? 0);
        $user = Auth::user();

        if (! $user || $requestId <= 0) {
            return;
        }

        DB::transaction(function () use ($requestId, $user) {
            $request = ProjectRequest::query()
                ->whereKey($requestId)
                ->where('requestor_id', $user->id)
                ->whereNull('first_reviewed_at')
                ->whereNull('locked_at')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $request->current_status;
            $previousStep = $request->current_step;
            $previousOwnerRole = $request->current_owner_role;

            $request->fill([
                'current_status' => 'withdrawn',
                'current_step' => null,
                'current_owner_role' => null,
                'current_owner_id' => null,
                'withdrawn_at' => now(),
                'cancelled_at' => now(),
                'locked_at' => now(),
                'last_transitioned_at' => now(),
                'latest_remarks' => 'Request withdrawn by requestor before first reviewer action.',
            ]);
            $request->save();

            RequestTransition::create([
                'project_request_id' => $request->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'withdrawn',
                'from_status' => $previousStatus,
                'to_status' => 'withdrawn',
                'from_step' => $previousStep,
                'to_step' => null,
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => null,
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => $request->is_late,
                'is_terminal' => true,
                'remarks' => 'Request withdrawn by requestor before reviewer pickup.',
                'context' => [
                    'withdrawn_before_review' => true,
                ],
                'acted_at' => now(),
            ]);
        });

        $this->dispatch('notify', type: 'warn', message: 'Request withdrawn successfully.');
    }

    protected function buildChain(ProjectRequest $request): array
    {
        if ($request->is_late) {
            return [
                ['role' => 'Farm Manager', 'st' => 'done'],
                ['role' => 'DH Gen Services', 'st' => in_array($request->current_status, ['rejected', 'returned_to_requestor'], true) ? 'rejected' : 'pending'],
                ['role' => 'Division Head', 'st' => $request->current_owner_role === 'division_head' ? 'pending' : 'waiting'],
            ];
        }

        return [
            ['role' => 'Farm Manager', 'st' => 'done'],
            ['role' => 'Division Head', 'st' => $request->current_owner_role === 'division_head' ? 'pending' : 'waiting'],
            ['role' => 'VP Gen Services', 'st' => $request->current_owner_role === 'vp_gen_services' ? 'pending' : 'waiting'],
            ['role' => 'DH Gen Services', 'st' => $request->current_owner_role === 'dh_gen_services' ? 'pending' : 'waiting'],
            ['role' => 'ED Manager', 'st' => $request->current_owner_role === 'ed_manager' ? 'pending' : 'waiting'],
        ];
    }

    public function getRequestsProperty(): Collection
    {
        return $this->loadRequests();
    }

    public function getShownRequestsProperty(): Collection
    {
        if ($this->filter === 'all') {
            return $this->requests;
        }

        return $this->requests->where('status', $this->filter)->values();
    }

    public function render()
    {
        return view('livewire.farm-manager.my-requests-page')
            ->layout('layouts.app', [
                'title' => 'My Requests | EngiStart',
                'header' => 'My Requests',
                'subheader' => 'Track the status of your submitted project requests.',
            ]);
    }
}
