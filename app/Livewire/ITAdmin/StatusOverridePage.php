<?php

namespace App\Livewire\ITAdmin;

use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;

class StatusOverridePage extends Component
{
    public string $search = '';

    public string $statusFilter = 'all';

    public int $perPage = 10;

    public int $page = 1;

    public array $overrideStatus = [];

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedStatusFilter(): void
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

    public function confirmOverride(string $requestId): void
    {
        $request = $this->filteredRequests->firstWhere('id', $requestId);

        if (! $request) {
            return;
        }

        $targetStatus = $this->overrideStatus[$requestId] ?? $request['status'];

        if ($targetStatus === $request['status']) {
            $this->dispatch('notify', type: 'warn', message: 'Select a different target status before applying an override.');

            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Apply status override?',
            'message' => 'This will forcibly change the current request status and log the override in the audit trail.',
            'tone' => 'warn',
            'confirmText' => 'Apply override',
            'confirmEvent' => 'itAdminOverrideConfirmed',
            'confirmTarget' => self::class,
            'summary' => [
                ['label' => 'Request ID', 'value' => $request['id']],
                ['label' => 'Request', 'value' => $request['title']],
                ['label' => 'Current Status', 'value' => $request['status_label']],
                ['label' => 'Override To', 'value' => $this->statusLabel($targetStatus)],
            ],
            'payload' => ['requestId' => $requestId],
        ])->to(ConfirmationModal::class);
    }

    #[On('itAdminOverrideConfirmed')]
    public function applyOverride(array $payload): void
    {
        $requestId = (string) ($payload['requestId'] ?? '');
        $user = Auth::user();

        abort_unless($user, 403);

        $targetStatus = $this->overrideStatus[$requestId] ?? null;

        if (! $targetStatus || ! array_key_exists($targetStatus, $this->statusMap())) {
            $this->dispatch('notify', type: 'danger', message: 'Invalid override status selected.');

            return;
        }

        DB::transaction(function () use ($requestId, $targetStatus, $user) {
            $request = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $request->current_status;
            $previousStep = $request->current_step;
            $previousOwnerRole = $request->current_owner_role;
            $previousOwnerId = $request->current_owner_id;

            $statusConfig = $this->statusMap()[$targetStatus];

            $request->fill([
                'current_status' => $targetStatus,
                'current_step' => $statusConfig['step'],
                'current_owner_role' => $statusConfig['owner_role'],
                'current_owner_id' => $statusConfig['owner_id'],
                'completed_at' => $statusConfig['is_terminal'] ? ($request->completed_at ?? now()) : null,
                'locked_at' => $statusConfig['lock'] ? ($request->locked_at ?? now()) : null,
                'last_transitioned_at' => now(),
                'latest_remarks' => 'Status overridden by IT Admin to ' . $this->statusLabel($targetStatus) . '.',
            ]);
            $request->save();

            RequestTransition::create([
                'project_request_id' => $request->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'status_override',
                'from_status' => $previousStatus,
                'to_status' => $targetStatus,
                'from_step' => $previousStep,
                'to_step' => $statusConfig['step'],
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => $statusConfig['owner_role'],
                'to_owner_id' => $statusConfig['owner_id'],
                'is_rework' => false,
                'is_exception_path' => $request->is_late,
                'is_terminal' => $statusConfig['is_terminal'],
                'remarks' => 'Status overridden by IT Admin from ' . $this->statusLabel($previousStatus) . ' to ' . $this->statusLabel($targetStatus) . '.',
                'context' => [
                    'review_stage' => 'it_admin_status_override',
                    'previous_owner_id' => $previousOwnerId,
                ],
                'acted_at' => now(),
            ]);
        });

        $this->dispatch('notify', type: 'warn', message: $requestId . ' status was overridden to ' . $this->statusLabel($targetStatus) . '.');
    }

    public function getRequestsProperty(): Collection
    {
        return ProjectRequest::query()
            ->with('requestor')
            ->whereNull('withdrawn_at')
            ->orderByDesc('last_transitioned_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (ProjectRequest $request): array {
                $currentStatus = $request->current_status ?? 'submitted';
                $this->overrideStatus[$request->request_number] = $this->overrideStatus[$request->request_number] ?? $currentStatus;

                return [
                    'id' => $request->request_number,
                    'title' => $request->title,
                    'farm' => $request->farm_name ?: 'System-wide',
                    'requestedBy' => $request->requestor?->name ?? 'Unknown requester',
                    'status' => $currentStatus,
                    'status_label' => $this->statusLabel($currentStatus),
                ];
            })
            ->values();
    }

    public function getFilteredRequestsProperty(): Collection
    {
        $items = $this->requests;

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $items = $items->filter(function (array $item) use ($needle): bool {
                return str_contains(mb_strtolower($item['id']), $needle)
                    || str_contains(mb_strtolower($item['title']), $needle)
                    || str_contains(mb_strtolower($item['farm']), $needle)
                    || str_contains(mb_strtolower($item['requestedBy']), $needle)
                    || str_contains(mb_strtolower($item['status_label']), $needle);
            })->values();
        }

        if ($this->statusFilter !== 'all') {
            $items = $items->where('status', $this->statusFilter)->values();
        }

        return $items;
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
            ->map(fn (array $item): array => ['value' => $item['status'], 'label' => $item['status_label']])
            ->unique('value')
            ->values()
            ->all();
    }

    public function getOverrideOptionsProperty(): array
    {
        return collect($this->statusMap())
            ->map(fn (array $config, string $status): array => ['value' => $status, 'label' => $this->statusLabel($status)])
            ->values()
            ->all();
    }

    protected function statusMap(): array
    {
        return [
            'submitted' => ['step' => 'division_head_review', 'owner_role' => 'division_head', 'owner_id' => null, 'is_terminal' => false, 'lock' => false],
            'recommended' => ['step' => 'vp_gen_services_approval', 'owner_role' => 'vp_gen_services', 'owner_id' => null, 'is_terminal' => false, 'lock' => true],
            'vp_approved' => ['step' => 'dh_gen_services_noting', 'owner_role' => 'dh_gen_services', 'owner_id' => null, 'is_terminal' => false, 'lock' => true],
            'noted' => ['step' => 'ed_manager_acceptance', 'owner_role' => 'ed_manager', 'owner_id' => null, 'is_terminal' => false, 'lock' => true],
            'accepted' => ['step' => 'completed', 'owner_role' => null, 'owner_id' => null, 'is_terminal' => true, 'lock' => true],
            'pending_vp' => ['step' => 'vp_gen_services_change_request_review', 'owner_role' => 'vp_gen_services', 'owner_id' => null, 'is_terminal' => false, 'lock' => true],
            'pending_it' => ['step' => 'it_admin_change_execution', 'owner_role' => 'it_admin', 'owner_id' => null, 'is_terminal' => false, 'lock' => true],
            'implemented' => ['step' => 'implementation_completed', 'owner_role' => null, 'owner_id' => null, 'is_terminal' => true, 'lock' => true],
            'returned_to_requestor' => ['step' => 'requestor_revision', 'owner_role' => 'farm_manager', 'owner_id' => null, 'is_terminal' => false, 'lock' => true],
            'rejected' => ['step' => 'terminal_rejection', 'owner_role' => null, 'owner_id' => null, 'is_terminal' => true, 'lock' => true],
            'cr_rejected' => ['step' => 'terminal_rejection', 'owner_role' => null, 'owner_id' => null, 'is_terminal' => true, 'lock' => true],
        ];
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'submitted' => 'Submitted',
            'recommended' => 'Recommended',
            'vp_approved' => 'VP Approved',
            'noted' => 'Noted',
            'accepted' => 'Accepted',
            'pending_vp' => 'Pending VP Review',
            'pending_it' => 'Pending IT Implementation',
            'implemented' => 'Implemented',
            'returned_to_requestor' => 'Returned to Requestor',
            'rejected' => 'Rejected',
            'cr_rejected' => 'Change Request Rejected',
            null => 'Unknown',
            default => str_replace('_', ' ', str($status)->title()),
        };
    }

    public function render()
    {
        return view('livewire.it-admin.status-override-page')
            ->layout('layouts.app', [
                'title' => 'Status Override | EngiStart',
                'header' => 'Status Override',
                'subheader' => 'Apply exceptional workflow changes with proper authorization.',
            ]);
    }
}
