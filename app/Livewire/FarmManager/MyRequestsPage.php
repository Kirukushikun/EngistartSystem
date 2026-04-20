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
    public string $search = '';

    public string $statusFilter = 'all';

    public string $sortBy = 'latest';

    public int $perPage = 5;

    public int $page = 1;

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedStatusFilter(): void
    {
        $this->page = 1;
    }

    public function updatedSortBy(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage(): void
    {
        if ($this->page < $this->totalPages) {
            $this->page++;
        }
    }

    protected function loadRequests(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return ProjectRequest::query()
            ->with('transitions')
            ->where('requestor_id', $user->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ProjectRequest $request): array => $this->mapRequestRecord($request))
            ->values();
    }

    protected function mapRequestRecord(ProjectRequest $request): array
    {
        $statusLabel = match ($request->current_status) {
            'late_pending' => 'Late Pending',
            'returned_to_requestor' => 'Returned to Requestor',
            default => str_replace('_', ' ', str($request->current_status)->title()),
        };

        return [
            'dbId' => $request->id,
            'id' => $request->request_number,
            'title' => $request->title,
            'needed' => optional($request->date_needed)->toDateString(),
            'submitted' => optional($request->submitted_at ?? $request->created_at)->toDateString(),
            'status' => $request->current_status,
            'statusLabel' => $statusLabel,
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
        $transitions = $request->transitions->keyBy('acted_by_role');

        if ($request->is_late) {
            return [
                ['role' => 'Farm Manager', 'st' => 'done'],
                [
                    'role' => 'DH Gen Services',
                    'st' => $request->current_owner_role === 'dh_gen_services'
                        ? 'pending'
                        : ($transitions->has('dh_gen_services') || in_array($request->current_owner_role, ['division_head', 'vp_gen_services', 'ed_manager'], true)
                            ? 'done'
                            : (in_array($request->current_status, ['rejected', 'returned_to_requestor'], true) ? 'rejected' : 'waiting')),
                ],
                [
                    'role' => 'Division Head',
                    'st' => $request->current_owner_role === 'division_head'
                        ? 'pending'
                        : ($transitions->has('division_head') || in_array($request->current_owner_role, ['vp_gen_services', 'dh_gen_services', 'ed_manager'], true)
                            ? 'done'
                            : 'waiting'),
                ],
            ];
        }

        return [
            ['role' => 'Farm Manager', 'st' => 'done'],
            [
                'role' => 'Division Head',
                'st' => $request->current_owner_role === 'division_head'
                    ? 'pending'
                    : ($transitions->has('division_head') || in_array($request->current_owner_role, ['vp_gen_services', 'dh_gen_services', 'ed_manager'], true)
                        ? 'done'
                        : (in_array($request->current_status, ['returned_to_requestor'], true) ? 'rejected' : 'waiting')),
            ],
            [
                'role' => 'VP Gen Services',
                'st' => $request->current_owner_role === 'vp_gen_services'
                    ? 'pending'
                    : ($transitions->has('vp_gen_services') || in_array($request->current_owner_role, ['dh_gen_services', 'ed_manager'], true)
                        ? 'done'
                        : (in_array($request->current_status, ['returned_to_requestor'], true) && $transitions->has('division_head') ? 'rejected' : 'waiting')),
            ],
            [
                'role' => 'DH Gen Services',
                'st' => $request->current_owner_role === 'dh_gen_services'
                    ? 'pending'
                    : ($transitions->has('dh_gen_services') || $request->current_owner_role === 'ed_manager'
                        ? 'done'
                        : 'waiting'),
            ],
            [
                'role' => 'ED Manager',
                'st' => $request->current_owner_role === 'ed_manager' ? 'pending' : ($transitions->has('ed_manager') ? 'done' : 'waiting'),
            ],
        ];
    }

    public function getRequestsProperty(): Collection
    {
        return $this->loadRequests();
    }

    public function getFilteredRequestsProperty(): Collection
    {
        $items = $this->requests;

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $items = $items->filter(function (array $request) use ($needle): bool {
                return str_contains(mb_strtolower($request['id']), $needle)
                    || str_contains(mb_strtolower($request['title']), $needle)
                    || str_contains(mb_strtolower($request['statusLabel']), $needle);
            })->values();
        }

        if ($this->statusFilter !== 'all') {
            $items = $items->where('status', $this->statusFilter)->values();
        }

        return match ($this->sortBy) {
            'needed_asc' => $items->sortBy('needed')->values(),
            'needed_desc' => $items->sortByDesc('needed')->values(),
            default => $items->sortByDesc('submitted')->values(),
        };
    }

    public function getPaginatedRequestsProperty(): Collection
    {
        if ($this->page > $this->totalPages) {
            $this->page = $this->totalPages;
        }

        return $this->filteredRequests
            ->slice(($this->page - 1) * $this->perPage, $this->perPage)
            ->values();
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->filteredRequests->count() / $this->perPage));
    }

    public function getShowingFromProperty(): int
    {
        if ($this->filteredRequests->isEmpty()) {
            return 0;
        }

        return (($this->page - 1) * $this->perPage) + 1;
    }

    public function getShowingToProperty(): int
    {
        if ($this->filteredRequests->isEmpty()) {
            return 0;
        }

        return min($this->page * $this->perPage, $this->filteredRequests->count());
    }

    public function getStatusOptionsProperty(): array
    {
        return $this->requests
            ->map(fn (array $request): array => ['value' => $request['status'], 'label' => $request['statusLabel']])
            ->unique('value')
            ->values()
            ->all();
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
