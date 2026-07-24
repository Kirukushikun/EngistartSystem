<?php

namespace App\Livewire\Engineer;

use App\Livewire\Concerns\BuildsRequestCardData;
use App\Livewire\Concerns\HasSimplePagination;
use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Support\ApprovalChainBuilder;
use App\Support\WorkflowNotifier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class InboxPage extends Component
{
    use BuildsRequestCardData;
    use HasSimplePagination;

    public array $remarks = [];

    public string $search = '';

    public string $sortBy = 'latest';

    public int $perPage = 5;

    public int $page = 1;

    public function confirmInitialize(string $requestId): void
    {
        $request = $this->loadItems()->firstWhere('id', $requestId);

        if (! $request || ! $request['isPendingHere']) {
            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Mark request as initialized?',
            'message' => 'Mark ' . $request['id'] . ' as initialized? This completes the project request workflow.',
            'tone' => 'success',
            'confirmText' => 'Mark Initialized',
            'confirmEvent' => 'engineerInitializationConfirmed',
            'confirmTarget' => self::class,
            'payload' => ['requestId' => $requestId],
        ])->to(ConfirmationModal::class);
    }

    #[On('engineerInitializationConfirmed')]
    public function markInitialized(array $payload): void
    {
        $requestId = (string) ($payload['requestId'] ?? '');
        $user = Auth::user();
        $remarks = trim($this->remarks[$requestId] ?? '');

        abort_unless($user, 403);

        $projectRequest = DB::transaction(function () use ($requestId, $remarks, $user) {
            $projectRequest = ProjectRequest::query()
                ->with('requestor')
                ->where('request_number', $requestId)
                ->where('current_owner_role', 'engineer')
                ->where('current_owner_id', $user->id)
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $projectRequest->fill([
                'current_status' => 'initialized',
                'current_step' => 'completed',
                'current_owner_role' => null,
                'current_owner_id' => null,
                'last_transitioned_at' => now(),
                'completed_at' => now(),
                'latest_remarks' => $remarks !== '' ? $remarks : 'Initialized by assigned Engineer.',
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => 'engineer',
                'action' => 'initialized',
                'from_status' => $previousStatus,
                'to_status' => 'initialized',
                'from_step' => $previousStep,
                'to_step' => 'completed',
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => null,
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => $projectRequest->is_late,
                'is_terminal' => true,
                'remarks' => $remarks !== '' ? $remarks : 'Initialized by assigned Engineer.',
                'context' => [
                    'review_stage' => 'engineer',
                ],
                'acted_at' => now(),
            ]);

            return $projectRequest;
        });

        if ($projectRequest->requestor) {
            WorkflowNotifier::notifyUser(
                $projectRequest->requestor,
                $projectRequest,
                'initialized',
                'Project Initialized — Workflow Complete',
                $projectRequest->request_number . ' — ' . $projectRequest->title . ' has been initialized. The workflow is now complete.'
            );
        }

        unset($this->remarks[$requestId]);

        $this->dispatch('notify', type: 'success', message: $requestId . ' was marked as initialized.');
    }

    public function updatedSearch(): void { $this->page = 1; }
    public function updatedSortBy(): void { $this->page = 1; }
    public function updatedPerPage(): void { $this->page = 1; }

    public function getItemsProperty(): Collection
    {
        return $this->loadItems();
    }

    public function getFilteredItemsProperty(): Collection
    {
        $items = $this->items;

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);
            $items = $items->filter(function (array $request) use ($needle): bool {
                return str_contains(mb_strtolower($request['id']), $needle)
                    || str_contains(mb_strtolower($request['title']), $needle)
                    || str_contains(mb_strtolower($request['farm']), $needle);
            })->values();
        }

        return match ($this->sortBy) {
            'needed_asc' => $items->sortBy('needed')->values(),
            'needed_desc' => $items->sortByDesc('needed')->values(),
            default => $items->sortByDesc('submitted')->values(),
        };
    }

    public function getPaginatedItemsProperty(): Collection
    {
        if ($this->page > $this->totalPages) {
            $this->page = $this->totalPages;
        }

        return $this->filteredItems->slice(($this->page - 1) * $this->perPage, $this->perPage)->values();
    }

    protected function paginationSourceCount(): int
    {
        return $this->filteredItems->count();
    }

    protected function loadItems(): Collection
    {
        $user = Auth::user();

        return ProjectRequest::query()
            ->with(['requestor', 'transitions.actedBy', 'attachments'])
            ->where(function ($query) use ($user) {
                $query->where('current_owner_role', 'engineer')
                    ->where('current_owner_id', $user?->id)
                    ->orWhereHas('transitions', function ($transitionQuery) use ($user) {
                        $transitionQuery->where('acted_by_role', 'engineer')->where('acted_by_id', $user?->id);
                    });
            })
            ->whereNull('withdrawn_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProjectRequest $request): array {
                $submittedAt = $request->submitted_at ?? $request->created_at;

                return [
                    'dbId' => $request->id,
                    'id' => $request->request_number,
                    'title' => $request->title,
                    'farm' => $request->farm_name ?? 'Farm not yet specified',
                    'type' => $request->request_type,
                    'purpose' => $request->purpose ?? 'No purpose provided',
                    'desc' => $request->description,
                    'chickin' => optional($request->chick_in_date)->format('Y-m-d'),
                    'cap' => $request->capacity,
                    'mtgDate' => optional($request->preferred_meeting_date)->format('Y-m-d'),
                    'mtgTime' => $request->preferred_meeting_time,
                    'needed' => optional($request->date_needed)->format('Y-m-d'),
                    'startDate' => optional($request->project_start_date)->format('Y-m-d'),
                    'completionDate' => optional($request->project_completion_date)->format('Y-m-d'),
                    'budgetCategory' => $this->budgetCategoryLabel($request->budget_category),
                    'jl' => data_get($request->meta, 'jl'),
                    'submitted' => optional($submittedAt)->format('Y-m-d'),
                    'status' => $request->current_status,
                    'statusLabel' => match ($request->current_status) {
                        'noted' => 'For Initialization',
                        'initialized' => 'Initialized',
                        default => str_replace('_', ' ', str($request->current_status)->title()),
                    },
                    'by' => $request->requestor?->name ?? 'Unknown requester',
                    'remarkHistory' => $this->buildRemarkHistory($request),
                    'attachments' => $this->buildAttachments($request),
                    'isLate' => $request->is_late,
                    'isPendingHere' => $request->current_owner_role === 'engineer',
                    'chain' => ApprovalChainBuilder::steps($request),
                ];
            })
            ->values();
    }

    public function render()
    {
        return view('livewire.engineer.inbox-page')
            ->layout('layouts.app', [
                'title' => 'For Initialization | EngiStart',
                'header' => 'For Initialization',
                'subheader' => 'Requests noted by DH Gen Services and assigned to you for initialization.',
            ]);
    }
}
