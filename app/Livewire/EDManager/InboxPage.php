<?php

namespace App\Livewire\EDManager;

use App\Livewire\Concerns\BuildsRequestCardData;
use App\Livewire\Concerns\HasSimplePagination;
use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Models\User;
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

    public array $selectedEngineer = [];

    public string $search = '';

    public string $typeFilter = 'all';

    public string $sortBy = 'latest';

    public int $perPage = 5;

    public int $page = 1;

    public function getEngineerOptionsProperty(): array
    {
        return User::query()
            ->where('role', 'engineer')
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function confirmAccept(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);

        if (! $request || ! $request['isPendingHere']) {
            return;
        }

        if (empty($this->selectedEngineer[$requestId])) {
            $this->dispatch('notify', type: 'warn', message: 'Select an engineer to assign this request to before accepting.');

            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Accept request?',
            'message' => 'Accept ' . $request['id'] . ' and assign it to the selected engineer, using the current remarks entered on this request?',
            'tone' => 'success',
            'confirmText' => 'Accept',
            'confirmEvent' => 'edManagerAcceptanceConfirmed',
            'confirmTarget' => self::class,
            'payload' => ['requestId' => $requestId],
        ])->to(ConfirmationModal::class);
    }

    public function confirmReturn(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);

        if (! $request || ! $request['isPendingHere']) {
            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Return request?',
            'message' => 'Return ' . $request['id'] . ' using the current remarks entered on this request?',
            'tone' => 'danger',
            'confirmText' => 'Return',
            'confirmEvent' => 'edManagerReturnConfirmed',
            'confirmTarget' => self::class,
            'payload' => ['requestId' => $requestId],
        ])->to(ConfirmationModal::class);
    }

    #[On('edManagerAcceptanceConfirmed')]
    public function accept(array $payload): void
    {
        $requestId = (string) ($payload['requestId'] ?? '');
        $user = Auth::user();
        $remarks = trim($this->remarks[$requestId] ?? '');
        $engineerId = (int) ($this->selectedEngineer[$requestId] ?? 0);

        abort_unless($user, 403);

        if (! $engineerId || ! array_key_exists($engineerId, $this->engineerOptions)) {
            $this->dispatch('notify', type: 'danger', message: 'Select a valid engineer before accepting this request.');

            return;
        }

        $projectRequest = DB::transaction(function () use ($requestId, $remarks, $user, $engineerId) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('current_owner_role', 'ed_manager')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $projectRequest->fill([
                'current_status' => 'accepted',
                'current_step' => 'dh_gen_services_noting',
                'current_owner_role' => 'dh_gen_services',
                'current_owner_id' => null,
                'assigned_engineer_id' => $engineerId,
                'first_reviewed_at' => $projectRequest->first_reviewed_at ?? now(),
                'locked_at' => $projectRequest->locked_at ?? now(),
                'last_transitioned_at' => now(),
                'latest_remarks' => $remarks !== '' ? $remarks : 'Accepted by ED Manager.',
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'accepted',
                'from_status' => $previousStatus,
                'to_status' => 'accepted',
                'from_step' => $previousStep,
                'to_step' => 'dh_gen_services_noting',
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => 'dh_gen_services',
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => $projectRequest->is_late,
                'is_terminal' => false,
                'remarks' => $remarks !== '' ? $remarks : 'Accepted by ED Manager.',
                'context' => [
                    'review_stage' => 'ed_manager',
                    'assigned_engineer_id' => $engineerId,
                ],
                'acted_at' => now(),
            ]);

            return $projectRequest;
        });

        WorkflowNotifier::notifyOwner(
            $projectRequest,
            'accepted',
            'Ready for DH Gen Services Noting',
            $projectRequest->request_number . ' — ' . $projectRequest->title . ' needs your noting.'
        );

        unset($this->remarks[$requestId], $this->selectedEngineer[$requestId]);

        $this->dispatch('notify', type: 'success', message: $requestId . ' was accepted and assigned successfully.');
    }

    #[On('edManagerReturnConfirmed')]
    public function returnRequest(array $payload): void
    {
        $requestId = (string) ($payload['requestId'] ?? '');
        $user = Auth::user();
        $remarks = trim($this->remarks[$requestId] ?? '');

        abort_unless($user, 403);

        $projectRequest = DB::transaction(function () use ($requestId, $remarks, $user) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('current_owner_role', 'ed_manager')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $projectRequest->fill([
                'current_status' => 'returned_to_requestor',
                'current_step' => 'requestor_revision',
                'current_owner_role' => $projectRequest->requestor_role,
                'current_owner_id' => $projectRequest->requestor_id,
                'first_reviewed_at' => $projectRequest->first_reviewed_at ?? now(),
                'locked_at' => $projectRequest->locked_at ?? now(),
                'last_transitioned_at' => now(),
                'latest_remarks' => $remarks !== '' ? $remarks : 'Returned to requestor by ED Manager.',
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'returned',
                'from_status' => $previousStatus,
                'to_status' => 'returned_to_requestor',
                'from_step' => $previousStep,
                'to_step' => 'requestor_revision',
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => $projectRequest->requestor_role,
                'to_owner_id' => $projectRequest->requestor_id,
                'is_rework' => true,
                'is_exception_path' => $projectRequest->is_late,
                'is_terminal' => false,
                'remarks' => $remarks !== '' ? $remarks : 'Returned to requestor by ED Manager.',
                'context' => [
                    'review_stage' => 'ed_manager',
                ],
                'acted_at' => now(),
            ]);

            return $projectRequest;
        });

        WorkflowNotifier::notifyOwner(
            $projectRequest,
            'returned_to_requestor',
            'Request Returned for Revision',
            $projectRequest->request_number . ' was returned by ED Manager.'
        );

        unset($this->remarks[$requestId]);

        $this->dispatch('notify', type: 'danger', message: $requestId . ' was returned to the requestor.');
    }

    public function updatedSearch(): void { $this->page = 1; }
    public function updatedTypeFilter(): void { $this->page = 1; }
    public function updatedSortBy(): void { $this->page = 1; }
    public function updatedPerPage(): void { $this->page = 1; }

    public function getInboxItemsProperty(): Collection
    {
        return $this->loadInboxItems();
    }

    public function getFilteredInboxItemsProperty(): Collection
    {
        $items = $this->inboxItems;

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $items = $items->filter(function (array $request) use ($needle): bool {
                return str_contains(mb_strtolower($request['id']), $needle)
                    || str_contains(mb_strtolower($request['title']), $needle)
                    || str_contains(mb_strtolower($request['farm']), $needle)
                    || str_contains(mb_strtolower($request['by']), $needle);
            })->values();
        }

        if ($this->typeFilter !== 'all') {
            $items = $items->where('type', $this->typeFilter)->values();
        }

        return match ($this->sortBy) {
            'needed_asc' => $items->sortBy('needed')->values(),
            'needed_desc' => $items->sortByDesc('needed')->values(),
            default => $items->sortByDesc('submitted')->values(),
        };
    }

    public function getPaginatedInboxItemsProperty(): Collection
    {
        if ($this->page > $this->totalPages) {
            $this->page = $this->totalPages;
        }

        return $this->filteredInboxItems
            ->slice(($this->page - 1) * $this->perPage, $this->perPage)
            ->values();
    }

    protected function paginationSourceCount(): int
    {
        return $this->filteredInboxItems->count();
    }

    public function getTypeOptionsProperty(): array
    {
        return $this->inboxItems
            ->pluck('type')
            ->unique()
            ->values()
            ->all();
    }

    protected function loadInboxItems(): Collection
    {
        return ProjectRequest::query()
            ->with(['requestor', 'transitions.actedBy', 'attachments'])
            ->where('request_type', '!=', 'Settings Change')
            ->where(function ($query) {
                $query->where('current_owner_role', 'ed_manager')
                    ->orWhereHas('transitions', function ($transitionQuery) {
                        $transitionQuery->where('acted_by_role', 'ed_manager');
                    });
            })
            ->whereNull('withdrawn_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProjectRequest $request): array {
                $submittedAt = $request->submitted_at ?? $request->created_at;
                $hasEdAction = $request->transitions->contains(fn ($transition) => $transition->acted_by_role === 'ed_manager');

                return [
                    'dbId' => $request->id,
                    'id' => $request->request_number,
                    'title' => $request->title,
                    'farm' => $request->farm_name ?? 'Farm not yet specified',
                    'type' => $request->request_type,
                    'purpose' => $request->purpose ?? 'No purpose provided',
                    'needed' => optional($request->date_needed)->format('Y-m-d'),
                    'submitted' => optional($submittedAt)->format('Y-m-d'),
                    'days' => $request->date_needed ? max(0, Carbon::today()->diffInDays($request->date_needed, false)) : 0,
                    'status' => $request->current_status,
                    'statusLabel' => match ($request->current_status) {
                        'vp_approved' => 'Awaiting Acceptance',
                        'accepted' => 'Accepted',
                        'noted' => 'Noted',
                        'returned_to_requestor' => 'Returned to Requestor',
                        'rejected' => 'Rejected',
                        default => str_replace('_', ' ', str($request->current_status)->title()),
                    },
                    'by' => $request->requestor?->name ?? 'Unknown requester',
                    'desc' => $request->description,
                    'chickin' => optional($request->chick_in_date)->format('Y-m-d'),
                    'cap' => $request->capacity,
                    'mtgDate' => optional($request->preferred_meeting_date)->format('Y-m-d'),
                    'mtgTime' => $request->preferred_meeting_time,
                    'budgetCategory' => $this->budgetCategoryLabel($request->budget_category),
                    'startDate' => optional($request->project_start_date)->format('Y-m-d'),
                    'completionDate' => optional($request->project_completion_date)->format('Y-m-d'),
                    'jl' => data_get($request->meta, 'jl'),
                    'remarkHistory' => $this->buildRemarkHistory($request),
                    'attachments' => $this->buildAttachments($request),
                    'isLate' => $request->is_late,
                    'isPendingHere' => $request->current_owner_role === 'ed_manager',
                    'isTransparentCopy' => $request->current_owner_role !== 'ed_manager' && $hasEdAction,
                    'chain' => ApprovalChainBuilder::steps($request),
                ];
            })
            ->values();
    }

    public function render()
    {
        return view('livewire.ed-manager.inbox-page')
            ->layout('layouts.app', [
                'title' => 'For Acceptance | EngiStart',
                'header' => 'For Acceptance',
                'subheader' => 'Review noted requests and issue the final acceptance decision.',
            ]);
    }
}
