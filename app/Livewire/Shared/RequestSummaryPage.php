<?php

namespace App\Livewire\Shared;

use App\Models\ProjectRequest;
use App\Support\ProjectTimelineCalculator;
use Illuminate\Support\Collection;
use Livewire\Component;

class RequestSummaryPage extends Component
{
    public string $farmFilter = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $sortDirection = 'asc';

    public int $perPage = 15;

    public int $page = 1;

    public function updatedFarmFilter(): void
    {
        $this->page = 1;
    }

    public function updatedDateFrom(): void
    {
        $this->page = 1;
    }

    public function updatedDateTo(): void
    {
        $this->page = 1;
    }

    public function updatedSortDirection(): void
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

    public function getFarmOptionsProperty(): array
    {
        return ProjectRequest::query()
            ->whereNotNull('farm_name')
            ->distinct()
            ->orderBy('farm_name')
            ->pluck('farm_name', 'farm_name')
            ->all();
    }

    public function getRowsProperty(): Collection
    {
        return ProjectRequest::query()
            ->with('requestor')
            ->when($this->farmFilter !== 'all', fn ($query) => $query->where('farm_name', $this->farmFilter))
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('submitted_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('submitted_at', '<=', $this->dateTo))
            ->orderBy('submitted_at', $this->sortDirection)
            ->get()
            ->map(function (ProjectRequest $request): array {
                $acceptanceDate = $request->transitions()->where('to_status', 'accepted')->value('acted_at');
                $requestedTimeline = $request->budget_category
                    ? ProjectTimelineCalculator::forCategory($request->budget_category, $request->submitted_at ?? $request->created_at)
                    : null;

                return [
                    'id' => $request->request_number,
                    'status' => $request->current_status,
                    'statusLabel' => $this->statusLabel($request->current_status),
                    'farm' => $request->farm_name ?? 'Farm not yet specified',
                    'title' => $request->title,
                    'dateOfRequest' => optional($request->submitted_at ?? $request->created_at)->format('Y-m-d'),
                    'acceptanceDate' => $acceptanceDate ? \Illuminate\Support\Carbon::parse($acceptanceDate)->format('Y-m-d') : '—',
                    'projectStartDate' => optional($request->project_start_date)->format('Y-m-d') ?: '—',
                    'projectCompletionDate' => optional($request->project_completion_date)->format('Y-m-d') ?: '—',
                    'requestedCompletionDate' => $requestedTimeline ? $requestedTimeline['completion_date']->format('Y-m-d') : '—',
                ];
            })
            ->values();
    }

    public function getPaginatedRowsProperty(): Collection
    {
        if ($this->page > $this->totalPages) {
            $this->page = $this->totalPages;
        }

        return $this->rows->slice(($this->page - 1) * $this->perPage, $this->perPage)->values();
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->rows->count() / $this->perPage));
    }

    public function getShowingFromProperty(): int
    {
        return $this->rows->isEmpty() ? 0 : (($this->page - 1) * $this->perPage) + 1;
    }

    public function getShowingToProperty(): int
    {
        return $this->rows->isEmpty() ? 0 : min($this->page * $this->perPage, $this->rows->count());
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'submitted' => 'Submitted',
            'recommended' => 'DH Recommended',
            'vp_approved' => 'VP Approved',
            'accepted' => 'Accepted',
            'noted' => 'Noted',
            'initialized' => 'Initialized',
            'returned_to_requestor' => 'Returned to Requestor',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
            'jl_pending' => 'JL Under Review',
            'jl_approved' => 'JL Approved',
            null => 'Unknown',
            default => str_replace('_', ' ', str($status)->title()),
        };
    }

    public function render()
    {
        return view('livewire.shared.request-summary-page')
            ->layout('layouts.app', [
                'title' => 'Project Request Summary | EngiStart',
                'header' => 'Project Request Summary',
                'subheader' => 'All project requests regardless of status, filterable by farm and date.',
            ]);
    }
}
