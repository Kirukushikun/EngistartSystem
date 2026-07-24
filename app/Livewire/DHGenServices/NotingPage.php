<?php

namespace App\Livewire\DHGenServices;

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
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

class NotingPage extends Component
{
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

    public function confirmNoteForward(string $requestId): void
    {
        $request = $this->loadNotingItems()->firstWhere('id', $requestId);

        if (! $request || ! $request['isPendingHere']) {
            return;
        }

        if (empty($this->selectedEngineer[$requestId])) {
            $this->dispatch('notify', type: 'warn', message: 'Select an engineer to route this request to before noting.');

            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Note request and forward?',
            'message' => 'Mark ' . $request['id'] . ' as noted and route it to the selected engineer for initialization?',
            'tone' => 'success',
            'confirmText' => 'Note & Forward',
            'confirmEvent' => 'dhGenServicesNotingConfirmed',
            'confirmTarget' => self::class,
            'payload' => ['requestId' => $requestId],
        ])->to(ConfirmationModal::class);
    }

    #[On('dhGenServicesNotingConfirmed')]
    public function noteForward(array $payload): void
    {
        $requestId = (string) ($payload['requestId'] ?? '');
        $user = Auth::user();
        $remarks = trim($this->remarks[$requestId] ?? '');
        $engineerId = (int) ($this->selectedEngineer[$requestId] ?? 0);

        abort_unless($user, 403);

        if (! $engineerId || ! array_key_exists($engineerId, $this->engineerOptions)) {
            $this->dispatch('notify', type: 'danger', message: 'Select a valid engineer before noting this request.');

            return;
        }

        $projectRequest = DB::transaction(function () use ($requestId, $remarks, $user, $engineerId) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('request_type', '!=', 'Settings Change')
                ->where('current_owner_role', 'dh_gen_services')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $projectRequest->fill([
                'current_status' => 'noted',
                'current_step' => 'engineer_initialization',
                'current_owner_role' => 'engineer',
                'current_owner_id' => $engineerId,
                'assigned_engineer_id' => $engineerId,
                'first_reviewed_at' => $projectRequest->first_reviewed_at ?? now(),
                'locked_at' => $projectRequest->locked_at ?? now(),
                'last_transitioned_at' => now(),
                'latest_remarks' => $remarks !== '' ? $remarks : 'Noted by DH Gen Services.',
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'noted',
                'from_status' => $previousStatus,
                'to_status' => 'noted',
                'from_step' => $previousStep,
                'to_step' => 'engineer_initialization',
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => 'engineer',
                'to_owner_id' => $engineerId,
                'is_rework' => false,
                'is_exception_path' => $projectRequest->is_late,
                'is_terminal' => false,
                'remarks' => $remarks !== '' ? $remarks : 'Noted by DH Gen Services.',
                'context' => [
                    'review_stage' => 'dh_gen_services',
                    'assigned_engineer_id' => $engineerId,
                ],
                'acted_at' => now(),
            ]);

            return $projectRequest;
        });

        WorkflowNotifier::notifyOwner(
            $projectRequest,
            'noted',
            'Project Assigned for Initialization',
            $projectRequest->request_number . ' — ' . $projectRequest->title . ' has been noted and assigned to you.'
        );

        unset($this->remarks[$requestId], $this->selectedEngineer[$requestId]);

        $this->dispatch('notify', type: 'info', message: $requestId . ' was noted and routed to the assigned engineer for initialization.');
    }

    public function updatedSearch(): void { $this->page = 1; }
    public function updatedTypeFilter(): void { $this->page = 1; }
    public function updatedSortBy(): void { $this->page = 1; }
    public function updatedPerPage(): void { $this->page = 1; }

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

    public function getItemsProperty(): Collection
    {
        return $this->loadNotingItems();
    }

    public function getFilteredItemsProperty(): Collection
    {
        $items = $this->items;

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

    public function getPaginatedItemsProperty(): Collection
    {
        if ($this->page > $this->totalPages) {
            $this->page = $this->totalPages;
        }

        return $this->filteredItems->slice(($this->page - 1) * $this->perPage, $this->perPage)->values();
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->filteredItems->count() / $this->perPage));
    }

    public function getShowingFromProperty(): int
    {
        return $this->filteredItems->isEmpty() ? 0 : (($this->page - 1) * $this->perPage) + 1;
    }

    public function getShowingToProperty(): int
    {
        return $this->filteredItems->isEmpty() ? 0 : min($this->page * $this->perPage, $this->filteredItems->count());
    }

    public function getTypeOptionsProperty(): array
    {
        return $this->items->pluck('type')->unique()->values()->all();
    }

    protected function loadNotingItems(): Collection
    {
        return ProjectRequest::query()
            ->with(['requestor', 'transitions.actedBy', 'attachments'])
            ->where('request_type', '!=', 'Settings Change')
            ->where(function ($query) {
                $query->where('current_owner_role', 'dh_gen_services')
                    ->orWhereHas('transitions', function ($transitionQuery) {
                        $transitionQuery->where('acted_by_role', 'dh_gen_services');
                    });
            })
            ->whereNull('withdrawn_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProjectRequest $request): array {
                $submittedAt = $request->submitted_at ?? $request->created_at;
                $hasDhGenAction = $request->transitions->contains(fn ($transition) => $transition->acted_by_role === 'dh_gen_services');

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
                        'vp_approved' => 'VP Approved',
                        'accepted' => 'Awaiting Noting',
                        'noted' => 'Noted',
                        'returned_to_requestor' => 'Returned to Requestor',
                        default => str_replace('_', ' ', str($request->current_status)->title()),
                    },
                    'by' => $request->requestor?->name ?? 'Unknown requester',
                    'desc' => $request->description,
                    'chickin' => optional($request->chick_in_date)->format('Y-m-d'),
                    'cap' => $request->capacity,
                    'mtgDate' => optional($request->preferred_meeting_date)->format('Y-m-d'),
                    'mtgTime' => $request->preferred_meeting_time,
                    'requestorRole' => $request->requestor_role ? $this->roleLabel($request->requestor_role) : null,
                    'budgetCategory' => $this->budgetCategoryLabel($request->budget_category),
                    'startDate' => optional($request->project_start_date)->format('Y-m-d'),
                    'completionDate' => optional($request->project_completion_date)->format('Y-m-d'),
                    'jl' => data_get($request->meta, 'jl'),
                    'remarkHistory' => $this->buildRemarkHistory($request),
                    'attachments' => $this->buildAttachments($request),
                    'isLate' => $request->is_late,
                    'isPendingHere' => $request->current_owner_role === 'dh_gen_services',
                    'isTransparentCopy' => $request->current_owner_role !== 'dh_gen_services' && $hasDhGenAction,
                    'chain' => ApprovalChainBuilder::steps($request),
                ];
            })
            ->values();
    }

    protected function buildAttachments(ProjectRequest $request): array
    {
        return $request->attachments
            ->where('is_active', true)
            ->filter(fn ($attachment) => in_array($attachment->attachment_type, ['justification_letter', 'supporting_document'], true))
            ->map(function ($attachment): array {
                return [
                    'label' => $attachment->attachment_type === 'justification_letter' ? 'JL File' : 'Attached File',
                    'name' => $attachment->original_name,
                    'url' => Storage::disk($attachment->disk)->url($attachment->path),
                ];
            })
            ->values()
            ->all();
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
                'tone' => $this->remarkTone($transition->action),
            ];
        }

        return $entries;
    }

    protected function budgetCategoryLabel(?string $category): ?string
    {
        return $category ? config('project_timelines.' . $category . '.label') : null;
    }

    protected function roleLabel(string $role): string
    {
        return match ($role) {
            'division_head' => 'Division Head',
            'vp_gen_services' => 'VP Gen Services',
            'dh_gen_services' => 'DH Gen Services',
            'ed_manager' => 'ED Manager',
            'engineer' => 'Engineer',
            default => str_replace('_', ' ', str($role)->title()),
        };
    }

    protected function remarkTone(string $action): string
    {
        return match ($action) {
            'approve', 'approved', 'noted', 'accepted' => 'success',
            'reject', 'rejected', 'return', 'returned' => 'danger',
            default => 'info',
        };
    }

    protected function remarkLabel(string $action): string
    {
        return match ($action) {
            'noted' => 'Noted',
            'approve', 'approved' => 'Approved',
            'accepted' => 'Accepted',
            'reject', 'rejected' => 'Rejected',
            'return', 'returned' => 'Returned',
            default => str_replace('_', ' ', str($action)->title()),
        };
    }

    public function render()
    {
        return view('livewire.dh-gen-services.noting-page')
            ->layout('layouts.app', [
                'title' => 'For Noting/Remarks | EngiStart',
                'header' => 'For Noting/Remarks',
                'subheader' => 'This section includes approved requests, including late filings, that now require DH Gen Services noting before forwarding to ED Manager.',
            ]);
    }
}
