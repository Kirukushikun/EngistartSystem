<?php

namespace App\Livewire\ITAdmin;

use App\Models\ProjectRequest;
use Illuminate\Support\Collection;
use Livewire\Component;

class AllRequestsPage extends Component
{
    public string $search = '';

    public string $statusFilter = 'all';

    public string $sortBy = 'latest';

    public int $perPage = 10;

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

    public function getRequestsProperty(): Collection
    {
        return ProjectRequest::query()
            ->with('requestor')
            ->orderByDesc('last_transitioned_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (ProjectRequest $request): array {
                $neededDate = $request->date_needed;
                $days = $neededDate ? now()->startOfDay()->diffInDays($neededDate->copy()->startOfDay(), false) : null;

                return [
                    'id' => $request->request_number,
                    'title' => $request->title,
                    'farm' => $request->farm_name ?: 'System-wide',
                    'by' => $request->requestor?->name ?? 'Unknown requester',
                    'type' => $request->request_type,
                    'needed' => $neededDate?->format('Y-m-d') ?? '—',
                    'days' => $days,
                    'routing' => $this->routingLabel($request),
                    'routing_key' => $request->request_type === 'Settings Change' ? 'settings_change' : ($request->is_late ? 'late' : 'standard'),
                    'status' => $request->current_status ?? 'unknown',
                    'status_label' => $this->statusLabel($request->current_status),
                    'submitted_sort' => ($request->submitted_at ?? $request->created_at)?->timestamp ?? 0,
                    'needed_sort' => $neededDate?->timestamp ?? PHP_INT_MAX,
                    'is_in_progress' => ! in_array($request->current_status, ['accepted', 'implemented', 'rejected', 'withdrawn'], true),
                    'is_completed' => in_array($request->current_status, ['accepted', 'implemented'], true),
                    'is_late' => $request->is_late,
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
                    || str_contains(mb_strtolower($item['by']), $needle)
                    || str_contains(mb_strtolower($item['type']), $needle)
                    || str_contains(mb_strtolower($item['routing']), $needle)
                    || str_contains(mb_strtolower($item['status_label']), $needle);
            })->values();
        }

        if ($this->statusFilter !== 'all') {
            $items = $items->where('status', $this->statusFilter)->values();
        }

        return match ($this->sortBy) {
            'needed_asc' => $items->sortBy('needed_sort')->values(),
            'needed_desc' => $items->sortByDesc('needed_sort')->values(),
            'oldest' => $items->sortBy('submitted_sort')->values(),
            default => $items->sortByDesc('submitted_sort')->values(),
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
            ->map(fn (array $item): array => ['value' => $item['status'], 'label' => $item['status_label']])
            ->unique('value')
            ->values()
            ->all();
    }

    public function getTotalCountProperty(): int
    {
        return $this->requests->count();
    }

    public function getInProgressCountProperty(): int
    {
        return $this->requests->where('is_in_progress', true)->count();
    }

    public function getCompletedCountProperty(): int
    {
        return $this->requests->where('is_completed', true)->count();
    }

    public function getLateFilingsCountProperty(): int
    {
        return $this->requests->where('is_late', true)->count();
    }

    protected function routingLabel(ProjectRequest $request): string
    {
        if ($request->request_type === 'Settings Change') {
            return 'Settings Change';
        }

        return $request->is_late ? 'Late Filing' : 'Standard';
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending_vp' => 'Pending VP Review',
            'pending_it' => 'Pending IT Implementation',
            'implemented' => 'Implemented',
            'cr_rejected' => 'Rejected',
            'recommended' => 'Recommended',
            'vp_approved' => 'VP Approved',
            'returned_to_requestor' => 'Returned to Requestor',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'noted' => 'Noted',
            'submitted' => 'Submitted',
            'withdrawn' => 'Withdrawn',
            null => 'Unknown',
            default => str_replace('_', ' ', str($status)->title()),
        };
    }

    public function render()
    {
        return view('livewire.it-admin.all-requests-page')
            ->layout('layouts.app', [
                'title' => 'All Requests | EngiStart',
                'header' => 'All Requests',
                'subheader' => 'Monitor request flow across the entire system.',
            ]);
    }
}
