<?php

namespace App\Livewire;

use App\Models\RequestTransition;
use App\Support\SettingsChangeValueFormatter;
use Illuminate\Support\Collection;
use Livewire\Component;

class HistoryPage extends Component
{
    public string $search = '';

    public string $actionFilter = 'all';

    public string $sortBy = 'latest';

    public int $perPage = 5;

    public int $page = 1;

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedActionFilter(): void
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

    public function getRoleProperty(): string
    {
        return (string) auth()->user()?->role;
    }

    public function getRoleLabelProperty(): string
    {
        return $this->roleConfig['label'] ?? 'Approver';
    }

    public function getRoleConfigProperty(): array
    {
        return $this->historyConfig()[$this->role] ?? [];
    }

    public function getPageTitleProperty(): string
    {
        return $this->roleConfig['page_title'] ?? 'History';
    }

    public function getPageDescriptionProperty(): string
    {
        return $this->roleConfig['page_description'] ?? 'Review the requests you have already acted on.';
    }

    public function getHistoryItemsProperty(): Collection
    {
        return RequestTransition::query()
            ->with(['projectRequest.requestor', 'actedBy'])
            ->where('acted_by_role', $this->role)
            ->whereHas('projectRequest', function ($query) {
                $query->whereNull('withdrawn_at');
            })
            ->orderByDesc('acted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (RequestTransition $transition): array {
                $request = $transition->projectRequest;
                $settingChangeDetails = $this->settingsChangeDetails($transition);

                return [
                    'id' => $request?->request_number ?? 'Unknown Request',
                    'title' => $request?->title ?? 'Untitled Request',
                    'farm' => $request?->farm_name ?? 'Farm not yet specified',
                    'type' => $request?->request_type ?? 'Unknown type',
                    'requestedBy' => $request?->requestor?->name ?? 'Unknown requester',
                    'requested_at' => optional($request?->submitted_at ?? $request?->created_at)->format('Y-m-d h:i A') ?? '—',
                    'requested_sort' => ($request?->submitted_at ?? $request?->created_at)?->timestamp ?? 0,
                    'action' => $this->historyActionLabel($transition),
                    'action_key' => $this->historyActionKey($transition),
                    'acted_at' => optional($transition->acted_at)->format('Y-m-d h:i A') ?? '—',
                    'acted_sort' => $transition->acted_at?->timestamp ?? 0,
                    'actor' => $transition->actedBy?->name ?? $this->roleLabel($transition->acted_by_role),
                    'current_status' => $request?->current_status ?? ($transition->to_status ?? 'unknown'),
                    'current_status_label' => $this->statusLabel($request?->current_status ?? $transition->to_status),
                    'remarks' => $transition->remarks ?: 'No remarks provided.',
                    'setting_change' => $settingChangeDetails,
                ];
            })
            ->values();
    }

    public function getFilteredHistoryItemsProperty(): Collection
    {
        $items = $this->historyItems;

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $items = $items->filter(function (array $item) use ($needle): bool {
                return str_contains(mb_strtolower($item['id']), $needle)
                    || str_contains(mb_strtolower($item['title']), $needle)
                    || str_contains(mb_strtolower($item['farm']), $needle)
                    || str_contains(mb_strtolower($item['requestedBy']), $needle)
                    || str_contains(mb_strtolower($item['action']), $needle)
                    || str_contains(mb_strtolower($item['type']), $needle);
            })->values();
        }

        if ($this->actionFilter !== 'all') {
            $items = $items->where('action_key', $this->actionFilter)->values();
        }

        return match ($this->sortBy) {
            'acted_asc' => $items->sortBy('acted_sort')->values(),
            'requested_asc' => $items->sortBy('requested_sort')->values(),
            'requested_desc' => $items->sortByDesc('requested_sort')->values(),
            default => $items->sortByDesc('acted_sort')->values(),
        };
    }

    public function getPaginatedHistoryItemsProperty(): Collection
    {
        if ($this->page > $this->totalPages) {
            $this->page = $this->totalPages;
        }

        return $this->filteredHistoryItems
            ->slice(($this->page - 1) * $this->perPage, $this->perPage)
            ->values();
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->filteredHistoryItems->count() / $this->perPage));
    }

    public function getShowingFromProperty(): int
    {
        if ($this->filteredHistoryItems->isEmpty()) {
            return 0;
        }

        return (($this->page - 1) * $this->perPage) + 1;
    }

    public function getShowingToProperty(): int
    {
        if ($this->filteredHistoryItems->isEmpty()) {
            return 0;
        }

        return min($this->page * $this->perPage, $this->filteredHistoryItems->count());
    }

    public function getActionOptionsProperty(): array
    {
        return $this->filteredActionOptions($this->historyItems);
    }

    protected function filteredActionOptions(Collection $items): array
    {
        return $items
            ->map(fn (array $item): array => ['value' => $item['action_key'], 'label' => $item['action']])
            ->unique('value')
            ->values()
            ->all();
    }

    protected function historyConfig(): array
    {
        return [
            'division_head' => [
                'label' => 'Division Head',
                'page_title' => 'History',
                'page_description' => 'Review the requests you already evaluated for recommendation.',
            ],
            'vp_gen_services' => [
                'label' => 'VP Gen Services',
                'page_title' => 'History',
                'page_description' => 'Review the requests and settings changes you already decided on.',
            ],
            'dh_gen_services' => [
                'label' => 'DH Gen Services',
                'page_title' => 'History',
                'page_description' => 'Review late-filing decisions and requests you already noted.',
            ],
            'ed_manager' => [
                'label' => 'ED Manager',
                'page_title' => 'History',
                'page_description' => 'Review the requests you already accepted or returned for revision.',
            ],
        ];
    }

    protected function historyActionLabel(RequestTransition $transition): string
    {
        $reviewStage = data_get($transition->context, 'review_stage');

        return match (true) {
            $reviewStage === 'settings_change_submission' => 'Submitted Change Request',
            $reviewStage === 'vp_gen_services_change_request' && $transition->action === 'approved' => 'Approved Change Request',
            $reviewStage === 'vp_gen_services_change_request' && $transition->action === 'rejected' => 'Rejected Change Request',
            $reviewStage === 'it_admin_change_execution' && $transition->action === 'implemented' => 'Implemented Change Request',
            $transition->acted_by_role === 'dh_gen_services' && $reviewStage === 'dh_gen_services_late_filing' && in_array($transition->action, ['approve', 'approved'], true) => 'Approved Late Filing',
            $transition->acted_by_role === 'dh_gen_services' && $reviewStage === 'dh_gen_services_late_filing' && in_array($transition->action, ['reject', 'rejected'], true) => 'Rejected Late Filing',
            default => match ($transition->action) {
                'recommend', 'recommended' => 'Recommended',
                'approve', 'approved' => 'Approved',
                'reject', 'rejected' => 'Rejected',
                'return', 'returned' => 'Returned',
                'accepted' => 'Accepted',
                'noted' => 'Noted',
                default => str_replace('_', ' ', str($transition->action)->title()),
            },
        };
    }

    protected function historyActionKey(RequestTransition $transition): string
    {
        $reviewStage = data_get($transition->context, 'review_stage');

        return match (true) {
            $reviewStage === 'settings_change_submission' => 'submitted',
            $reviewStage === 'vp_gen_services_change_request' && $transition->action === 'approved' => 'approved',
            $reviewStage === 'vp_gen_services_change_request' && $transition->action === 'rejected' => 'cr_rejected',
            $reviewStage === 'it_admin_change_execution' && $transition->action === 'implemented' => 'implemented',
            $transition->acted_by_role === 'dh_gen_services' && $reviewStage === 'dh_gen_services_late_filing' && in_array($transition->action, ['approve', 'approved'], true) => 'approved_late',
            $transition->acted_by_role === 'dh_gen_services' && $reviewStage === 'dh_gen_services_late_filing' && in_array($transition->action, ['reject', 'rejected'], true) => 'rejected_late',
            default => $transition->action,
        };
    }

    protected function roleLabel(?string $role): string
    {
        return match ($role) {
            'division_head' => 'Division Head',
            'vp_gen_services' => 'VP Gen Services',
            'dh_gen_services' => 'DH Gen Services',
            'ed_manager' => 'ED Manager',
            'farm_manager' => 'Farm Manager',
            default => str_replace('_', ' ', str((string) $role)->title()),
        };
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending_vp' => 'Pending VP Review',
            'pending_it' => 'Ready for IT Implementation',
            'implemented' => 'Implemented',
            'cr_rejected' => 'Rejected',
            'recommended' => 'DH Recommended',
            'vp_approved' => 'VP Approved',
            'returned_to_requestor' => 'Returned to Requestor',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'noted' => 'Noted',
            'submitted' => 'Submitted',
            null => 'Unknown',
            default => str_replace('_', ' ', str($status)->title()),
        };
    }

    protected function settingsChangeDetails(RequestTransition $transition): ?array
    {
        if (data_get($transition->context, 'review_stage') !== 'settings_change_submission') {
            return null;
        }

        $settingKey = data_get($transition->context, 'setting_key')
            ?? data_get($transition->projectRequest?->meta, 'setting_change.setting_key');

        $currentValue = data_get($transition->context, 'current_value')
            ?? data_get($transition->projectRequest?->meta, 'setting_change.current_value');

        $proposedValue = data_get($transition->context, 'proposed_value')
            ?? data_get($transition->projectRequest?->meta, 'setting_change.proposed_value');

        return [
            'current_value' => SettingsChangeValueFormatter::format($settingKey, $currentValue),
            'proposed_value' => SettingsChangeValueFormatter::format($settingKey, $proposedValue),
        ];
    }

    public function render()
    {
        abort_unless(array_key_exists($this->role, $this->historyConfig()), 403);

        return view('livewire.history-page')
            ->layout('layouts.app', [
                'title' => $this->pageTitle . ' | EngiStart',
                'header' => $this->pageTitle,
                'subheader' => $this->pageDescription,
            ]);
    }
}
