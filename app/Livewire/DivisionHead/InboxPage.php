<?php

namespace App\Livewire\DivisionHead;

use App\Livewire\Concerns\BuildsRequestCardData;
use App\Livewire\Concerns\HasSimplePagination;
use App\Livewire\Concerns\RequestsMeetingReschedule;
use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Support\ApprovalChainBuilder;
use App\Support\WorkflowNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class InboxPage extends Component
{
    use BuildsRequestCardData;
    use HasSimplePagination;
    use RequestsMeetingReschedule;

    public ?string $openRequestId = null;

    public array $remarks = [];

    public string $search = '';

    public string $typeFilter = 'all';

    public string $sortBy = 'latest';

    public int $perPage = 5;

    public int $page = 1;

    public function toggleRequest(string $requestId): void
    {
        $this->openRequestId = $this->openRequestId === $requestId ? null : $requestId;
    }

    public function confirmRecommend(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);

        if (! $request || ! $request['isPendingHere']) {
            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Recommend request for approval?',
            'message' => 'Recommend ' . $request['id'] . ' using the current remarks entered on this request?',
            'tone' => 'success',
            'confirmText' => 'Recommend',
            'confirmEvent' => 'divisionHeadRecommendationConfirmed',
            'confirmTarget' => self::class,
            'payload' => ['requestId' => $requestId],
        ])->to(ConfirmationModal::class);
    }

    public function confirmReject(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);

        if (! $request || ! $request['isPendingHere']) {
            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Reject request?',
            'message' => 'Reject ' . $request['id'] . ' using the current remarks entered on this request?',
            'tone' => 'danger',
            'confirmText' => 'Reject',
            'confirmEvent' => 'divisionHeadRejectionConfirmed',
            'confirmTarget' => self::class,
            'payload' => ['requestId' => $requestId],
        ])->to(ConfirmationModal::class);
    }

    #[On('divisionHeadRecommendationConfirmed')]
    public function recommend(array $payload): void
    {
        $requestId = (string) ($payload['requestId'] ?? '');
        $remarks = trim($this->remarks[$requestId] ?? '');

        $user = Auth::user();

        abort_unless($user, 403);

        $projectRequest = DB::transaction(function () use ($requestId, $remarks, $user) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('current_owner_role', 'division_head')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $isJlReview = $projectRequest->current_step === 'division_head_jl_review';

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $nextStatus = $isJlReview ? 'jl_pending' : 'recommended';
            $nextStep = $isJlReview ? 'vp_gen_services_jl_review' : 'vp_gen_services_approval';
            $defaultRemarks = $isJlReview ? 'JL recommended for review by Division Head.' : 'Recommended for approval by Division Head.';

            $projectRequest->fill([
                'current_status' => $nextStatus,
                'current_step' => $nextStep,
                'current_owner_role' => 'vp_gen_services',
                'current_owner_id' => null,
                'exception_status' => $isJlReview ? 'pending_vp_gen_services' : $projectRequest->exception_status,
                'first_reviewed_at' => $projectRequest->first_reviewed_at ?? now(),
                'locked_at' => $projectRequest->locked_at ?? now(),
                'last_transitioned_at' => now(),
                'latest_remarks' => $remarks !== '' ? $remarks : $defaultRemarks,
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => $isJlReview ? 'jl_recommended' : 'recommended',
                'from_status' => $previousStatus,
                'to_status' => $nextStatus,
                'from_step' => $previousStep,
                'to_step' => $nextStep,
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => 'vp_gen_services',
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => $isJlReview,
                'is_terminal' => false,
                'remarks' => $remarks !== '' ? $remarks : $defaultRemarks,
                'context' => [
                    'review_stage' => 'division_head',
                ],
                'acted_at' => now(),
            ]);

            return $projectRequest;
        });

        WorkflowNotifier::notifyOwner(
            $projectRequest,
            $projectRequest->current_step === 'vp_gen_services_jl_review' ? 'jl_recommended' : 'recommended',
            $projectRequest->current_step === 'vp_gen_services_jl_review' ? 'JL Ready for VP Review' : 'Ready for VP Approval',
            $projectRequest->request_number . ' — ' . $projectRequest->title . ' needs your review.'
        );

        unset($this->remarks[$requestId]);

        $this->dispatch('notify', type: 'info', message: $requestId . ' was processed and routed onward.');
    }

    #[On('divisionHeadRejectionConfirmed')]
    public function reject(array $payload): void
    {
        $requestId = (string) ($payload['requestId'] ?? '');
        $remarks = trim($this->remarks[$requestId] ?? '');

        $user = Auth::user();

        abort_unless($user, 403);

        $projectRequest = DB::transaction(function () use ($requestId, $remarks, $user) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('current_owner_role', 'division_head')
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
                'latest_remarks' => $remarks !== '' ? $remarks : 'Returned to requestor by Division Head.',
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'rejected',
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
                'remarks' => $remarks !== '' ? $remarks : 'Returned to requestor by Division Head.',
                'context' => [
                    'review_stage' => 'division_head',
                ],
                'acted_at' => now(),
            ]);

            return $projectRequest;
        });

        WorkflowNotifier::notifyOwner(
            $projectRequest,
            'returned_to_requestor',
            'Request Returned for Revision',
            $projectRequest->request_number . ' was returned by Division Head.'
        );

        unset($this->remarks[$requestId]);

        $this->dispatch('notify', type: 'danger', message: $requestId . ' was rejected.');
    }

    #[On('divisionHeadRescheduleConfirmed')]
    public function reschedule(array $payload): void
    {
        $this->performReschedule((string) ($payload['requestId'] ?? ''), 'division_head');
    }

    protected function rescheduleConfirmEventName(): string
    {
        return 'divisionHeadRescheduleConfirmed';
    }

    protected function rescheduleRoleLabel(): string
    {
        return 'Division Head';
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedTypeFilter(): void
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
                $query->where('current_owner_role', 'division_head')
                    ->orWhereHas('transitions', function ($transitionQuery) {
                        $transitionQuery->where('acted_by_role', 'division_head');
                    });
            })
            ->whereNull('withdrawn_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProjectRequest $request): array {
                $submittedAt = $request->submitted_at ?? $request->created_at;
                $hasDivisionHeadAction = $request->transitions->contains(fn ($transition) => $transition->acted_by_role === 'division_head');
                $latestTransition = $request->transitions->sortByDesc('acted_at')->first();

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
                        'submitted' => 'Submitted',
                        'recommended' => 'DH Recommended',
                        'vp_approved' => 'VP Approved',
                        'returned_to_requestor' => 'Returned to Requestor',
                        'rejected' => 'Rejected',
                        'withdrawn' => 'Withdrawn',
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
                    'isPendingHere' => $request->current_owner_role === 'division_head',
                    'isTransparentCopy' => $request->current_owner_role !== 'division_head' && $hasDivisionHeadAction,
                    'chain' => ApprovalChainBuilder::steps($request),
                ];
            })
            ->values();
    }

    public function render()
    {
        return view('livewire.division-head.inbox-page')
            ->layout('layouts.app', [
                'title' => 'For Recommendation | EngiStart',
                'header' => 'For Recommendation',
                'subheader' => 'Review incoming project requests and recommend them for approval.',
            ]);
    }
}
