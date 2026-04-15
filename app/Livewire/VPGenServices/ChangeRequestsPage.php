<?php

namespace App\Livewire\VPGenServices;

use Illuminate\Support\Collection;
use Livewire\Component;

class ChangeRequestsPage extends Component
{
    public array $remarks = [];

    public ?string $actionMessage = null;

    public string $actionTone = 'info';

    public string $search = '';

    public string $statusFilter = 'pending_vp';

    public string $sortBy = 'latest';

    public int $perPage = 5;

    public int $page = 1;

    public function approve(string $requestId): void
    {
        $request = $this->loadChangeRequests()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'info';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} was approved and routed for implementation." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
            : 'Dummy action completed.';
    }

    public function reject(string $requestId): void
    {
        $request = $this->loadChangeRequests()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'danger';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} was rejected by VP Gen Services." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
            : 'Dummy action completed.';
    }

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

    public function getChangeRequestsProperty(): Collection
    {
        return $this->loadChangeRequests();
    }

    public function getFilteredChangeRequestsProperty(): Collection
    {
        $items = $this->changeRequests;

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $items = $items->filter(function (array $request) use ($needle): bool {
                return str_contains(mb_strtolower($request['id']), $needle)
                    || str_contains(mb_strtolower($request['setting']), $needle)
                    || str_contains(mb_strtolower($request['requestedBy']), $needle)
                    || str_contains(mb_strtolower($request['requestedRole']), $needle);
            })->values();
        }

        if ($this->statusFilter !== 'all') {
            $items = $items->where('status', $this->statusFilter)->values();
        }

        return match ($this->sortBy) {
            'setting_asc' => $items->sortBy('setting')->values(),
            'setting_desc' => $items->sortByDesc('setting')->values(),
            default => $items->sortByDesc('requestedAt')->values(),
        };
    }

    public function getPaginatedChangeRequestsProperty(): Collection
    {
        if ($this->page > $this->totalPages) {
            $this->page = $this->totalPages;
        }

        return $this->filteredChangeRequests
            ->slice(($this->page - 1) * $this->perPage, $this->perPage)
            ->values();
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->filteredChangeRequests->count() / $this->perPage));
    }

    public function getShowingFromProperty(): int
    {
        if ($this->filteredChangeRequests->isEmpty()) {
            return 0;
        }

        return (($this->page - 1) * $this->perPage) + 1;
    }

    public function getShowingToProperty(): int
    {
        if ($this->filteredChangeRequests->isEmpty()) {
            return 0;
        }

        return min($this->page * $this->perPage, $this->filteredChangeRequests->count());
    }

    protected function loadChangeRequests(): Collection
    {
        return collect([
            [
                'id' => 'SCR-2026-001',
                'setting' => 'Small Project Cost Threshold',
                'key' => 'small_threshold',
                'oldVal' => '₱200,000',
                'newVal' => '₱250,000',
                'reason' => 'Project costs have increased due to inflation. The current threshold no longer reflects small vs large project distinction accurately.',
                'requestedBy' => 'Engr. D. Baniaga',
                'requestedRole' => 'ED Manager',
                'requestedAt' => '2026-03-22',
                'status' => 'pending_vp',
            ],
            [
                'id' => 'SCR-2026-002',
                'setting' => 'Required Advance Submission (days)',
                'key' => 'lead_time_days',
                'oldVal' => '45 days',
                'newVal' => '50 days',
                'reason' => 'Recent scheduling bottlenecks suggest a slightly longer lead time will improve planning and cross-functional coordination.',
                'requestedBy' => 'Ancel Roque',
                'requestedRole' => 'DH Gen Services',
                'requestedAt' => '2026-03-20',
                'status' => 'pending_vp',
            ],
            [
                'id' => 'SCR-2026-003',
                'setting' => 'Acceptance Lead Time (days)',
                'key' => 'acceptance_lead_time',
                'oldVal' => '3 days',
                'newVal' => '5 days',
                'reason' => 'The operations team needs additional time to validate readiness before final acceptance is completed in the field.',
                'requestedBy' => 'Engr. D. Baniaga',
                'requestedRole' => 'ED Manager',
                'requestedAt' => '2026-03-18',
                'status' => 'pending_vp',
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.vp-gen-services.change-requests-page')
            ->layout('layouts.app');
    }
}
