<?php

namespace App\Livewire\Shared;

use App\Livewire\Concerns\BuildsRequestCardData;
use App\Livewire\Concerns\HasSimplePagination;
use App\Models\ProjectReferenceLink;
use App\Models\ProjectRequest;
use App\Support\ProjectTimelineCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RequestSummaryPage extends Component
{
    use BuildsRequestCardData;
    use HasSimplePagination;

    public string $farmFilter = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $sortDirection = 'asc';

    public int $perPage = 15;

    public int $page = 1;

    public array $newLinkUrl = [];

    public array $newLinkLabel = [];

    public function addReferenceLink(int $requestId): void
    {
        $this->validate([
            'newLinkUrl.' . $requestId => ['required', 'url', 'max:2048'],
            'newLinkLabel.' . $requestId => ['nullable', 'string', 'max:255'],
        ]);

        $projectRequest = ProjectRequest::findOrFail($requestId);

        $projectRequest->referenceLinks()->create([
            'added_by_id' => Auth::id(),
            'url' => $this->newLinkUrl[$requestId],
            'label' => $this->newLinkLabel[$requestId] ?: null,
        ]);

        unset($this->newLinkUrl[$requestId], $this->newLinkLabel[$requestId]);
    }

    public function removeReferenceLink(int $linkId): void
    {
        $link = ProjectReferenceLink::find($linkId);
        $user = Auth::user();

        if (! $link || ! $user) {
            return;
        }

        if ($link->added_by_id !== $user->id && $user->role !== 'it_admin') {
            return;
        }

        $link->delete();
    }

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
        $user = Auth::user();

        return ProjectRequest::query()
            ->with(['requestor', 'attachments', 'assignedEngineer', 'referenceLinks.addedBy'])
            ->when($user, function ($query) use ($user) {
                // Farm Manager's "involvement" is personal (they're the requestor), not
                // role-wide -- scoping by acted_by_role would leak every other Farm
                // Manager's requests to each other. Every other role is a shared role-wide
                // inbox, so scoping by role (currently theirs, or previously acted on by
                // their role) is correct there.
                if ($user->role === 'farm_manager') {
                    $query->where('requestor_id', $user->id);

                    return;
                }

                $query->where(function ($query) use ($user) {
                    $query->where('current_owner_role', $user->role)
                        ->orWhereHas('transitions', function ($transitionQuery) use ($user) {
                            $transitionQuery->where('acted_by_role', $user->role);
                        });
                });
            })
            ->when($this->farmFilter !== 'all', fn ($query) => $query->where('farm_name', $this->farmFilter))
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('submitted_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('submitted_at', '<=', $this->dateTo))
            ->orderBy('submitted_at', $this->sortDirection)
            ->get()
            ->map(function (ProjectRequest $request) use ($user): array {
                $acceptanceDate = $request->transitions()->where('to_status', 'accepted')->value('acted_at');
                $requestedTimeline = $request->budget_category
                    ? ProjectTimelineCalculator::forCategory($request->budget_category, $request->submitted_at ?? $request->created_at)
                    : null;

                return [
                    'id' => $request->request_number,
                    'requestId' => $request->id,
                    'status' => $request->current_status,
                    'statusLabel' => ProjectRequest::statusLabel($request->current_status),
                    'farm' => $request->farm_name ?? 'Farm not yet specified',
                    'title' => $request->title,
                    'by' => $request->requestor?->name ?? 'Unknown requester',
                    'budgetCategory' => $this->budgetCategoryLabel($request->budget_category),
                    'dateOfRequest' => optional($request->submitted_at ?? $request->created_at)->format('M j, Y'),
                    'acceptanceDate' => $acceptanceDate ? \Illuminate\Support\Carbon::parse($acceptanceDate)->format('M j, Y') : '—',
                    'projectStartDate' => optional($request->project_start_date)->format('M j, Y') ?: '—',
                    'projectCompletionDate' => optional($request->project_completion_date)->format('M j, Y') ?: '—',
                    'requestedCompletionDate' => $requestedTimeline ? $requestedTimeline['completion_date']->format('M j, Y') : '—',
                    'type' => $request->request_type,
                    'purpose' => $request->purpose,
                    'desc' => $request->description,
                    'jl' => data_get($request->meta, 'jl'),
                    'assignedEngineer' => $request->assignedEngineer?->name ?? '—',
                    'attachments' => $this->buildAttachments($request),
                    'referenceLinks' => $request->referenceLinks->map(fn (\App\Models\ProjectReferenceLink $link) => [
                        'id' => $link->id,
                        'url' => $link->url,
                        'label' => $link->label ?: $link->url,
                        'addedBy' => $link->addedBy?->name,
                        'canDelete' => $user && ($link->added_by_id === $user->id || $user->role === 'it_admin'),
                    ])->values()->all(),
                ];
            })
            ->values();
    }

    public function getHasAssignedEngineersProperty(): bool
    {
        return $this->rows->contains(fn (array $row) => $row['assignedEngineer'] !== '—');
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
                'subheader' => 'Requests currently waiting on you or that you\'ve acted on before, filterable by farm and date.',
            ]);
    }
}
