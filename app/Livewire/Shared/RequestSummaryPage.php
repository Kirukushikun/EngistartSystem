<?php

namespace App\Livewire\Shared;

use App\Livewire\Concerns\HasSimplePagination;
use App\Models\ProjectRequest;
use App\Support\ProjectTimelineCalculator;
use Illuminate\Support\Collection;
use Livewire\Component;

class RequestSummaryPage extends Component
{
    use HasSimplePagination;

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
                    'statusLabel' => ProjectRequest::statusLabel($request->current_status),
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

    protected function paginationSourceCount(): int
    {
        return $this->rows->count();
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
