<?php

namespace App\Livewire\VPGenServices;

use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class InboxPage extends Component
{
    public array $remarks = [];

    public string $search = '';

    public string $typeFilter = 'all';

    public string $sortBy = 'latest';

    public int $perPage = 5;

    public int $page = 1;

    public function confirmApprove(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);

        if (! $request || ! $request['isPendingHere']) {
            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Approve request?',
            'message' => 'Approve ' . $request['id'] . ' using the current remarks entered on this request?',
            'tone' => 'success',
            'confirmText' => 'Approve',
            'confirmEvent' => 'vpApprovalConfirmed',
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
            'confirmEvent' => 'vpRejectionConfirmed',
            'confirmTarget' => self::class,
            'payload' => ['requestId' => $requestId],
        ])->to(ConfirmationModal::class);
    }

    #[On('vpApprovalConfirmed')]
    public function approve(array $payload): void
    {
        $requestId = (string) ($payload['requestId'] ?? '');
        $user = Auth::user();
        $remarks = trim($this->remarks[$requestId] ?? '');

        abort_unless($user, 403);

        DB::transaction(function () use ($requestId, $remarks, $user) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('current_owner_role', 'vp_gen_services')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $isLateRerouteApproval = $projectRequest->current_status === 'for_vp_reroute_approval'
                || $projectRequest->current_step === 'vp_gen_services_reroute_review';

            $projectRequest->fill([
                'current_status' => $isLateRerouteApproval ? 'for_dh_final_reroute_approval' : 'vp_approved',
                'current_step' => $isLateRerouteApproval ? 'division_head_final_reroute_review' : 'dh_gen_services_noting',
                'current_owner_role' => $isLateRerouteApproval ? 'division_head' : 'dh_gen_services',
                'current_owner_id' => null,
                'first_reviewed_at' => $projectRequest->first_reviewed_at ?? now(),
                'locked_at' => $projectRequest->locked_at ?? now(),
                'last_transitioned_at' => now(),
                'latest_remarks' => $remarks !== '' ? $remarks : ($isLateRerouteApproval ? 'Late filing reroute approved by VP Gen Services.' : 'Approved by VP Gen Services.'),
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'approved',
                'from_status' => $previousStatus,
                'to_status' => $isLateRerouteApproval ? 'for_dh_final_reroute_approval' : 'vp_approved',
                'from_step' => $previousStep,
                'to_step' => $isLateRerouteApproval ? 'division_head_final_reroute_review' : 'dh_gen_services_noting',
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => $isLateRerouteApproval ? 'division_head' : 'dh_gen_services',
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => $projectRequest->is_late,
                'is_terminal' => false,
                'remarks' => $remarks !== '' ? $remarks : ($isLateRerouteApproval ? 'Late filing reroute approved by VP Gen Services.' : 'Approved by VP Gen Services.'),
                'context' => [
                    'review_stage' => $isLateRerouteApproval ? 'vp_gen_services_reroute_request' : 'vp_gen_services',
                ],
                'acted_at' => now(),
            ]);
        });

        unset($this->remarks[$requestId]);

        $this->dispatch('notify', type: 'info', message: $requestId . ' was approved and routed onward.');
    }

    #[On('vpRejectionConfirmed')]
    public function reject(array $payload): void
    {
        $requestId = (string) ($payload['requestId'] ?? '');
        $user = Auth::user();
        $remarks = trim($this->remarks[$requestId] ?? '');

        abort_unless($user, 403);

        DB::transaction(function () use ($requestId, $remarks, $user) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('current_owner_role', 'vp_gen_services')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $isLateRerouteApproval = $projectRequest->current_status === 'for_vp_reroute_approval'
                || $projectRequest->current_step === 'vp_gen_services_reroute_review';

            $projectRequest->fill([
                'current_status' => $isLateRerouteApproval ? 'rejected' : 'returned_to_requestor',
                'current_step' => $isLateRerouteApproval ? 'terminal_rejection' : 'requestor_revision',
                'current_owner_role' => $isLateRerouteApproval ? null : $projectRequest->requestor_role,
                'current_owner_id' => $isLateRerouteApproval ? null : $projectRequest->requestor_id,
                'first_reviewed_at' => $projectRequest->first_reviewed_at ?? now(),
                'locked_at' => $isLateRerouteApproval ? now() : ($projectRequest->locked_at ?? now()),
                'last_transitioned_at' => now(),
                'latest_remarks' => $remarks !== '' ? $remarks : ($isLateRerouteApproval ? 'Late filing reroute rejected by VP Gen Services.' : 'Returned to requestor by VP Gen Services.'),
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'rejected',
                'from_status' => $previousStatus,
                'to_status' => $isLateRerouteApproval ? 'rejected' : 'returned_to_requestor',
                'from_step' => $previousStep,
                'to_step' => $isLateRerouteApproval ? 'terminal_rejection' : 'requestor_revision',
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => $isLateRerouteApproval ? null : $projectRequest->requestor_role,
                'to_owner_id' => $isLateRerouteApproval ? null : $projectRequest->requestor_id,
                'is_rework' => ! $isLateRerouteApproval,
                'is_exception_path' => $projectRequest->is_late,
                'is_terminal' => $isLateRerouteApproval,
                'remarks' => $remarks !== '' ? $remarks : ($isLateRerouteApproval ? 'Late filing reroute rejected by VP Gen Services.' : 'Returned to requestor by VP Gen Services.'),
                'context' => [
                    'review_stage' => $isLateRerouteApproval ? 'vp_gen_services_reroute_request' : 'vp_gen_services',
                ],
                'acted_at' => now(),
            ]);
        });

        unset($this->remarks[$requestId]);

        $this->dispatch('notify', type: 'danger', message: $requestId . ' was rejected.');
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

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->filteredInboxItems->count() / $this->perPage));
    }

    public function getShowingFromProperty(): int
    {
        if ($this->filteredInboxItems->isEmpty()) {
            return 0;
        }

        return (($this->page - 1) * $this->perPage) + 1;
    }

    public function getShowingToProperty(): int
    {
        if ($this->filteredInboxItems->isEmpty()) {
            return 0;
        }

        return min($this->page * $this->perPage, $this->filteredInboxItems->count());
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
            ->with(['requestor', 'transitions.actedBy'])
            ->where('request_type', '!=', 'Settings Change')
            ->where(function ($query) {
                $query->where('current_owner_role', 'vp_gen_services')
                    ->orWhereHas('transitions', function ($transitionQuery) {
                        $transitionQuery->where('acted_by_role', 'vp_gen_services');
                    });
            })
            ->whereNull('withdrawn_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProjectRequest $request): array {
                $submittedAt = $request->submitted_at ?? $request->created_at;
                $hasVpAction = $request->transitions->contains(fn ($transition) => $transition->acted_by_role === 'vp_gen_services');
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
                        'returned_to_division_head' => 'Returned to Division Head',
                        'for_dh_reroute_approval' => 'For Approval of Division Head',
                        'for_vp_reroute_approval' => 'For Approval of VP Gen Services',
                        'for_dh_final_reroute_approval' => 'For Approval of Division Head',
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
                    'remarkHistory' => $this->buildRemarkHistory($request),
                    'isLate' => $request->is_late,
                    'isPendingHere' => $request->current_owner_role === 'vp_gen_services',
                    'isTransparentCopy' => $request->current_owner_role !== 'vp_gen_services' && $hasVpAction,
                    'chain' => $this->buildApprovalChain($request),
                ];
            })
            ->values();
    }

    protected function buildRemarkHistory(ProjectRequest $request): array
    {
        $entries = [];

        foreach ($request->transitions->sortBy('acted_at') as $transition) {
            if ($transition->acted_by_role === 'farm_manager' || blank($transition->remarks)) {
                continue;
            }

            $entries[] = [
                'role' => $this->roleLabel($transition->acted_by_role),
                'actor' => $transition->actedBy?->name ?? 'Unknown approver',
                'label' => $this->remarkLabel($transition->action),
                'remarks' => $transition->remarks,
                'date' => optional($transition->acted_at)->format('Y-m-d h:i A'),
                'tone' => $this->remarkTone($transition->action),
            ];
        }

        return $entries;
    }

    protected function roleLabel(string $role): string
    {
        return match ($role) {
            'division_head' => 'Division Head',
            'vp_gen_services' => 'VP Gen Services',
            'dh_gen_services' => 'DH Gen Services',
            'ed_manager' => 'ED Manager',
            'farm_manager' => 'Farm Manager',
            default => str_replace('_', ' ', str($role)->title()),
        };
    }

    protected function remarkTone(string $action): string
    {
        return match ($action) {
            'recommend', 'approve', 'accepted', 'noted' => 'success',
            'reject', 'rejected', 'return', 'returned' => 'danger',
            default => 'info',
        };
    }

    protected function remarkLabel(string $action): string
    {
        return match ($action) {
            'recommend' => 'Recommended',
            'approve' => 'Approved',
            'noted' => 'Noted',
            'accepted' => 'Accepted',
            'reject', 'rejected' => 'Rejected',
            'return', 'returned' => 'Returned',
            default => str_replace('_', ' ', str($action)->title()),
        };
    }

    protected function buildApprovalChain(ProjectRequest $request): array
    {
        $transitions = $request->transitions->keyBy(function (RequestTransition $transition) {
            if ($transition->acted_by_role === 'dh_gen_services' && data_get($transition->context, 'review_stage') === 'dh_gen_services_late_filing') {
                return 'dh_gen_services_late';
            }

            if ($transition->acted_by_role === 'division_head' && data_get($transition->context, 'review_stage') === 'division_head_reroute_request') {
                return 'division_head_reroute_request';
            }

            if ($transition->acted_by_role === 'division_head' && data_get($transition->context, 'review_stage') === 'division_head_final_reroute') {
                return 'division_head_final_reroute';
            }

            if ($transition->acted_by_role === 'vp_gen_services' && data_get($transition->context, 'review_stage') === 'vp_gen_services_reroute_request') {
                return 'vp_gen_services_reroute_request';
            }

            return $transition->acted_by_role;
        });

        if ($request->is_late) {
            $hasInitialDh = $transitions->has('division_head');
            $hasLateDh = $transitions->has('dh_gen_services_late');
            $hasRerouteVp = $transitions->has('vp_gen_services_reroute_request');
            $hasFinalDh = $transitions->has('division_head_final_reroute');
            $hasNormalVp = $transitions->has('vp_gen_services');
            $hasNormalDhGen = $transitions->has('dh_gen_services') && ! $transitions->has('dh_gen_services_late');
            $hasEd = $transitions->has('ed_manager');
            $isAwaitingOptionalReroute = $request->current_status === 'returned_to_division_head'
                || ($request->current_owner_role === 'division_head' && $request->current_step === 'division_head_reroute_review' && ! $hasRerouteVp && ! $hasFinalDh);
            $isRerouteFlow = in_array($request->current_status, ['for_vp_reroute_approval', 'for_dh_final_reroute_approval', 'rejected'], true)
                || $hasRerouteVp || $hasFinalDh;

            $chain = [
                [
                    'role' => 'Farm Manager',
                    'user' => $request->requestor?->name,
                    'action' => 'Submitted (Late Filing)',
                    'date' => optional($request->submitted_at ?? $request->created_at)?->format('Y-m-d'),
                    'st' => 'done',
                ],
                [
                    'role' => 'Division Head',
                    'user' => $transitions->get('division_head')?->actedBy?->name,
                    'action' => 'Late Filing Endorsement',
                    'date' => optional($transitions->get('division_head')?->acted_at)->format('Y-m-d'),
                    'st' => $request->current_owner_role === 'division_head' && $request->current_step === 'division_head_review'
                        ? 'pending'
                        : ($hasInitialDh ? ($request->current_status === 'returned_to_requestor' && ! $hasLateDh ? 'rejected' : 'done') : 'waiting'),
                ],
                [
                    'role' => 'DH Gen Services',
                    'user' => $transitions->get('dh_gen_services_late')?->actedBy?->name,
                    'action' => 'Late Filing Review',
                    'date' => optional($transitions->get('dh_gen_services_late')?->acted_at)->format('Y-m-d'),
                    'st' => $request->current_owner_role === 'dh_gen_services' && $request->current_status === 'late_pending'
                        ? 'pending'
                        : ($hasLateDh ? ($isAwaitingOptionalReroute ? 'rejected' : 'done') : 'waiting'),
                ],
            ];

            if (! $hasInitialDh || ! $hasLateDh) {
                return $chain;
            }

            if ($isAwaitingOptionalReroute) {
                return $chain;
            }

            if ($isRerouteFlow) {
                $chain[] = [
                    'role' => 'VP Gen Services',
                    'user' => $transitions->get('vp_gen_services_reroute_request')?->actedBy?->name,
                    'action' => 'Reroute Review',
                    'date' => optional($transitions->get('vp_gen_services_reroute_request')?->acted_at)->format('Y-m-d'),
                    'st' => $request->current_owner_role === 'vp_gen_services' && $request->current_step === 'vp_gen_services_reroute_review'
                        ? 'pending'
                        : ($hasRerouteVp ? ($request->current_status === 'rejected' && ! $hasFinalDh ? 'rejected' : 'done') : 'waiting'),
                ];

                if (! $hasRerouteVp && $request->current_owner_role === 'vp_gen_services') {
                    return $chain;
                }

                $chain[] = [
                    'role' => 'Division Head',
                    'user' => $transitions->get('division_head_final_reroute')?->actedBy?->name,
                    'action' => 'Confirmation / FYI',
                    'date' => optional($transitions->get('division_head_final_reroute')?->acted_at)->format('Y-m-d'),
                    'st' => $request->current_owner_role === 'division_head' && $request->current_step === 'division_head_final_reroute_review'
                        ? 'pending'
                        : ($hasFinalDh ? ($request->current_status === 'rejected' ? 'rejected' : 'done') : 'waiting'),
                ];
            } else {
                $chain[] = [
                    'role' => 'VP Gen Services',
                    'user' => $transitions->get('vp_gen_services')?->actedBy?->name,
                    'action' => 'Approval',
                    'date' => optional($transitions->get('vp_gen_services')?->acted_at)->format('Y-m-d'),
                    'st' => $request->current_owner_role === 'vp_gen_services' ? 'pending' : ($hasNormalVp ? 'done' : 'waiting'),
                ];
            }

            $chain[] = [
                'role' => 'DH Gen Services',
                'user' => $transitions->get('dh_gen_services')?->actedBy?->name,
                'action' => 'Noted',
                'date' => optional($transitions->get('dh_gen_services')?->acted_at)->format('Y-m-d'),
                'st' => $request->current_owner_role === 'dh_gen_services' && $request->current_status !== 'late_pending'
                    ? 'pending'
                    : ($hasNormalDhGen ? 'done' : 'waiting'),
            ];
            $chain[] = [
                'role' => 'ED Manager',
                'user' => $transitions->get('ed_manager')?->actedBy?->name,
                'action' => 'Acceptance',
                'date' => optional($transitions->get('ed_manager')?->acted_at)->format('Y-m-d'),
                'st' => $request->current_owner_role === 'ed_manager' ? 'pending' : ($hasEd ? 'done' : 'waiting'),
            ];

            return $chain;
        }

        $transitions = $request->transitions->keyBy('acted_by_role');

        return [
            [
                'role' => 'Farm Manager',
                'user' => $request->requestor?->name,
                'action' => 'Submitted',
                'date' => optional($request->submitted_at ?? $request->created_at)?->format('Y-m-d'),
                'st' => 'done',
            ],
            [
                'role' => 'Division Head',
                'user' => $transitions->get('division_head')?->actedBy?->name,
                'action' => 'Recommendation',
                'date' => optional($transitions->get('division_head')?->acted_at)->format('Y-m-d'),
                'st' => $request->current_owner_role === 'division_head' ? 'pending' : ($transitions->has('division_head') ? ($request->current_status === 'returned_to_requestor' && ! $transitions->has('vp_gen_services') ? 'rejected' : 'done') : 'waiting'),
            ],
            [
                'role' => 'VP Gen Services',
                'user' => $transitions->get('vp_gen_services')?->actedBy?->name,
                'action' => 'Approval',
                'date' => optional($transitions->get('vp_gen_services')?->acted_at)->format('Y-m-d'),
                'st' => $request->current_owner_role === 'vp_gen_services' ? 'pending' : ($transitions->has('vp_gen_services') ? ($request->current_status === 'returned_to_requestor' ? 'rejected' : 'done') : 'waiting'),
            ],
            [
                'role' => 'DH Gen Services',
                'user' => $transitions->get('dh_gen_services')?->actedBy?->name,
                'action' => 'Noted',
                'date' => optional($transitions->get('dh_gen_services')?->acted_at)->format('Y-m-d'),
                'st' => $request->current_owner_role === 'dh_gen_services' ? 'pending' : ($transitions->has('dh_gen_services') ? 'done' : 'waiting'),
            ],
            [
                'role' => 'ED Manager',
                'user' => $transitions->get('ed_manager')?->actedBy?->name,
                'action' => 'Acceptance',
                'date' => optional($transitions->get('ed_manager')?->acted_at)->format('Y-m-d'),
                'st' => $request->current_owner_role === 'ed_manager' ? 'pending' : ($transitions->has('ed_manager') ? 'done' : 'waiting'),
            ],
        ];
    }

    public function render()
    {
        return view('livewire.vp-gen-services.inbox-page')
            ->layout('layouts.app', [
                'title' => 'For Approval | EngiStart',
                'header' => 'For Approval',
                'subheader' => 'Review Division Head recommendations and issue VP approval decisions.',
            ]);
    }
}
