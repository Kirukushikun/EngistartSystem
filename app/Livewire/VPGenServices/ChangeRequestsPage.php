<?php

namespace App\Livewire\VPGenServices;

use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Support\SettingsChangeValueFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $user = Auth::user();
        $remarks = trim($this->remarks[$requestId] ?? '');

        abort_unless($user, 403);

        DB::transaction(function () use ($requestId, $remarks, $user) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('request_type', 'Settings Change')
                ->where('current_owner_role', 'vp_gen_services')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $projectRequest->fill([
                'current_status' => 'pending_it',
                'current_step' => 'it_admin_change_execution',
                'current_owner_role' => 'it_admin',
                'current_owner_id' => null,
                'first_reviewed_at' => $projectRequest->first_reviewed_at ?? now(),
                'locked_at' => $projectRequest->locked_at ?? now(),
                'last_transitioned_at' => now(),
                'latest_remarks' => $remarks !== '' ? $remarks : 'Approved by VP Gen Services for IT implementation.',
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'approved',
                'from_status' => $previousStatus,
                'to_status' => 'pending_it',
                'from_step' => $previousStep,
                'to_step' => 'it_admin_change_execution',
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => 'it_admin',
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => false,
                'is_terminal' => false,
                'remarks' => $remarks !== '' ? $remarks : 'Approved by VP Gen Services for IT implementation.',
                'context' => [
                    'review_stage' => 'vp_gen_services_change_request',
                    'setting_change' => data_get($projectRequest->meta, 'setting_change', []),
                ],
                'acted_at' => now(),
            ]);
        });

        unset($this->remarks[$requestId]);

        $this->actionTone = 'info';
        $this->actionMessage = $requestId . ' was approved and routed to IT Admin for implementation.';
    }

    public function reject(string $requestId): void
    {
        $user = Auth::user();
        $remarks = trim($this->remarks[$requestId] ?? '');

        abort_unless($user, 403);

        DB::transaction(function () use ($requestId, $remarks, $user) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('request_type', 'Settings Change')
                ->where('current_owner_role', 'vp_gen_services')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $projectRequest->fill([
                'current_status' => 'cr_rejected',
                'current_step' => 'terminal_rejection',
                'current_owner_role' => null,
                'current_owner_id' => null,
                'first_reviewed_at' => $projectRequest->first_reviewed_at ?? now(),
                'locked_at' => now(),
                'last_transitioned_at' => now(),
                'completed_at' => now(),
                'latest_remarks' => $remarks !== '' ? $remarks : 'Rejected by VP Gen Services.',
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'rejected',
                'from_status' => $previousStatus,
                'to_status' => 'cr_rejected',
                'from_step' => $previousStep,
                'to_step' => 'terminal_rejection',
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => null,
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => false,
                'is_terminal' => true,
                'remarks' => $remarks !== '' ? $remarks : 'Rejected by VP Gen Services.',
                'context' => [
                    'review_stage' => 'vp_gen_services_change_request',
                    'setting_change' => data_get($projectRequest->meta, 'setting_change', []),
                ],
                'acted_at' => now(),
            ]);
        });

        unset($this->remarks[$requestId]);

        $this->actionTone = 'danger';
        $this->actionMessage = $requestId . ' was rejected by VP Gen Services.';
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
            default => $items->sortByDesc('requestedSort')->values(),
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
        return ProjectRequest::query()
            ->with('requestor')
            ->where('request_type', 'Settings Change')
            ->where(function ($query) {
                $query->where('current_owner_role', 'vp_gen_services')
                    ->orWhereHas('transitions', function ($transitionQuery) {
                        $transitionQuery->where('acted_by_role', 'vp_gen_services')
                            ->whereIn('to_status', ['pending_it', 'cr_rejected']);
                    });
            })
            ->whereNull('withdrawn_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProjectRequest $request): array {
                $settingChange = data_get($request->meta, 'setting_change', []);
                $settingKey = data_get($settingChange, 'setting_key');

                return [
                    'id' => $request->request_number,
                    'setting' => data_get($settingChange, 'setting_label', $request->title),
                    'key' => $settingKey,
                    'oldVal' => SettingsChangeValueFormatter::format($settingKey, data_get($settingChange, 'current_value', '—')),
                    'newVal' => SettingsChangeValueFormatter::format($settingKey, data_get($settingChange, 'proposed_value', '—')),
                    'reason' => $request->description,
                    'requestedBy' => $request->requestor?->name ?? 'Unknown requester',
                    'requestedRole' => str_replace('_', ' ', str($request->requestor_role)->title()),
                    'requestedAt' => optional($request->submitted_at)->format('Y-m-d h:i A') ?? '—',
                    'requestedSort' => $request->submitted_at?->timestamp ?? 0,
                    'status' => $request->current_status,
                ];
            })
            ->values();
    }

    public function render()
    {
        return view('livewire.vp-gen-services.change-requests-page')
            ->layout('layouts.app', [
                'title' => 'Change Requests | EngiStart',
                'header' => 'Change Requests',
                'subheader' => 'Review settings change requests before they are forwarded for implementation.',
            ]);
    }
}
