<?php

namespace App\Livewire\ITAdmin;

use App\Models\RequestTransition;
use Illuminate\Support\Collection;
use Livewire\Component;

class AuditTrailPage extends Component
{
    public string $search = '';

    public string $actionFilter = 'all';

    public string $roleFilter = 'all';

    public string $sortBy = 'latest';

    public int $perPage = 10;

    public int $page = 1;

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedActionFilter(): void
    {
        $this->page = 1;
    }

    public function updatedRoleFilter(): void
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

    public function getLogsProperty(): Collection
    {
        return RequestTransition::query()
            ->with(['projectRequest', 'actedBy'])
            ->orderByDesc('acted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (RequestTransition $transition): array {
                $request = $transition->projectRequest;

                return [
                    'ts' => optional($transition->acted_at)->format('Y-m-d h:i A') ?? '—',
                    'ts_sort' => $transition->acted_at?->timestamp ?? 0,
                    'user' => $transition->actedBy?->name ?? $this->roleLabel($transition->acted_by_role),
                    'role' => $this->roleLabel($transition->acted_by_role),
                    'role_key' => (string) $transition->acted_by_role,
                    'action' => $this->actionLabel($transition),
                    'action_key' => $this->actionKey($transition),
                    'id' => $request?->request_number ?? '—',
                    'title' => $request?->title ?? 'Untitled Request',
                    'note' => $transition->remarks
                        ?: ('Status changed from ' . ($this->statusLabel($transition->from_status)) . ' to ' . ($this->statusLabel($transition->to_status)) . '.'),
                ];
            })
            ->values();
    }

    public function getFilteredLogsProperty(): Collection
    {
        $items = $this->logs;

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $items = $items->filter(function (array $item) use ($needle): bool {
                return str_contains(mb_strtolower($item['user']), $needle)
                    || str_contains(mb_strtolower($item['role']), $needle)
                    || str_contains(mb_strtolower($item['action']), $needle)
                    || str_contains(mb_strtolower($item['id']), $needle)
                    || str_contains(mb_strtolower($item['title']), $needle)
                    || str_contains(mb_strtolower($item['note']), $needle);
            })->values();
        }

        if ($this->actionFilter !== 'all') {
            $items = $items->where('action_key', $this->actionFilter)->values();
        }

        if ($this->roleFilter !== 'all') {
            $items = $items->where('role_key', $this->roleFilter)->values();
        }

        return match ($this->sortBy) {
            'oldest' => $items->sortBy('ts_sort')->values(),
            default => $items->sortByDesc('ts_sort')->values(),
        };
    }

    public function getPaginatedLogsProperty(): Collection
    {
        if ($this->page > $this->totalPages) {
            $this->page = $this->totalPages;
        }

        return $this->filteredLogs
            ->slice(($this->page - 1) * $this->perPage, $this->perPage)
            ->values();
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->filteredLogs->count() / $this->perPage));
    }

    public function getShowingFromProperty(): int
    {
        if ($this->filteredLogs->isEmpty()) {
            return 0;
        }

        return (($this->page - 1) * $this->perPage) + 1;
    }

    public function getShowingToProperty(): int
    {
        if ($this->filteredLogs->isEmpty()) {
            return 0;
        }

        return min($this->page * $this->perPage, $this->filteredLogs->count());
    }

    public function getActionOptionsProperty(): array
    {
        return $this->logs
            ->map(fn (array $item): array => ['value' => $item['action_key'], 'label' => $item['action']])
            ->unique('value')
            ->values()
            ->all();
    }

    public function getRoleOptionsProperty(): array
    {
        return $this->logs
            ->map(fn (array $item): array => ['value' => $item['role_key'], 'label' => $item['role']])
            ->unique('value')
            ->values()
            ->all();
    }

    protected function actionLabel(RequestTransition $transition): string
    {
        return match ($transition->action) {
            'recommended', 'recommend' => 'Recommended',
            'approved', 'approve' => 'Approved',
            'rejected', 'reject' => 'Rejected',
            'returned', 'return' => 'Returned',
            'accepted' => 'Accepted',
            'noted' => 'Noted',
            'implemented' => 'Implemented',
            'withdrawn' => 'Withdrawn',
            'requested_reroute' => 'Requested Reroute',
            default => str_replace('_', ' ', str($transition->action)->title()),
        };
    }

    protected function actionKey(RequestTransition $transition): string
    {
        return (string) $transition->action;
    }

    protected function roleLabel(?string $role): string
    {
        return match ($role) {
            'division_head' => 'Division Head',
            'vp_gen_services' => 'VP Gen Services',
            'dh_gen_services' => 'DH Gen Services',
            'ed_manager' => 'ED Manager',
            'farm_manager' => 'Farm Manager',
            'it_admin' => 'IT Admin',
            default => str_replace('_', ' ', str((string) $role)->title()),
        };
    }

    protected function statusLabel(?string $status): string
    {
        return $status === null ? 'none' : str_replace('_', ' ', str($status)->title());
    }

    public function render()
    {
        return view('livewire.it-admin.audit-trail-page')
            ->layout('layouts.app', [
                'title' => 'Audit Trail | EngiStart',
                'header' => 'Audit Trail',
                'subheader' => 'Track system activities and approval actions.',
            ]);
    }
}
