<?php

namespace App\Livewire;

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
        return collect($this->historyConfig()[$this->role]['items'] ?? []);
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
            'acted_asc' => $items->sortBy('acted_at')->values(),
            'requested_asc' => $items->sortBy('requested_at')->values(),
            'requested_desc' => $items->sortByDesc('requested_at')->values(),
            default => $items->sortByDesc('acted_at')->values(),
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
                'items' => [
                    [
                        'id' => 'APIS-2026-002',
                        'title' => 'Feed Storage Expansion',
                        'farm' => 'Farm C – Concepcion, Tarlac',
                        'type' => 'Building',
                        'requestedBy' => 'Pedro Reyes',
                        'requested_at' => '2026-03-10',
                        'action' => 'Recommended',
                        'action_key' => 'recommended',
                        'acted_at' => '2026-03-14',
                        'current_status' => 'recommended',
                        'remarks' => 'Aligned with projected storage demand and budget assumptions.',
                    ],
                    [
                        'id' => 'APIS-2026-009',
                        'title' => 'Drainage Line Rehabilitation',
                        'farm' => 'Farm B – Capas, Tarlac',
                        'type' => 'Infrastructure',
                        'requestedBy' => 'Maria Cruz',
                        'requested_at' => '2026-03-07',
                        'action' => 'Returned',
                        'action_key' => 'returned',
                        'acted_at' => '2026-03-09',
                        'current_status' => 'submitted',
                        'remarks' => 'Requested additional site sketches before endorsement.',
                    ],
                    [
                        'id' => 'APIS-2026-010',
                        'title' => 'Service Road Extension',
                        'farm' => 'Farm D – Angeles, Pampanga',
                        'type' => 'Infrastructure',
                        'requestedBy' => 'Ramon Torres',
                        'requested_at' => '2026-03-05',
                        'action' => 'Rejected',
                        'action_key' => 'rejected',
                        'acted_at' => '2026-03-06',
                        'current_status' => 'rejected',
                        'remarks' => 'Proposal lacked revised cost basis and supporting scope details.',
                    ],
                ],
            ],
            'vp_gen_services' => [
                'label' => 'VP Gen Services',
                'page_title' => 'History',
                'page_description' => 'Review the requests and settings changes you already decided on.',
                'items' => [
                    [
                        'id' => 'APIS-2026-003',
                        'title' => 'Irrigation System Phase 2',
                        'farm' => 'Farm B – Capas, Tarlac',
                        'type' => 'Infrastructure',
                        'requestedBy' => 'Maria Cruz',
                        'requested_at' => '2026-03-05',
                        'action' => 'Approved',
                        'action_key' => 'approved',
                        'acted_at' => '2026-03-12',
                        'current_status' => 'vp_approved',
                        'remarks' => 'Approved after verifying urgency, scope, and downstream readiness.',
                    ],
                    [
                        'id' => 'SCR-2026-014',
                        'title' => 'Settings Change: Reminder Interval',
                        'farm' => 'System-wide',
                        'type' => 'Settings Change',
                        'requestedBy' => 'Engr. D. Baniaga',
                        'requested_at' => '2026-03-11',
                        'action' => 'Rejected',
                        'action_key' => 'rejected',
                        'acted_at' => '2026-03-13',
                        'current_status' => 'cr_rejected',
                        'remarks' => 'Requested a more complete impact analysis before approval.',
                    ],
                    [
                        'id' => 'SCR-2026-011',
                        'title' => 'Settings Change: Lead Time Threshold',
                        'farm' => 'System-wide',
                        'type' => 'Settings Change',
                        'requestedBy' => 'Ancel Roque',
                        'requested_at' => '2026-03-09',
                        'action' => 'Approved',
                        'action_key' => 'approved',
                        'acted_at' => '2026-03-10',
                        'current_status' => 'pending_it',
                        'remarks' => 'Approved for IT implementation after workflow review.',
                    ],
                ],
            ],
            'dh_gen_services' => [
                'label' => 'DH Gen Services',
                'page_title' => 'History',
                'page_description' => 'Review late-filing decisions and requests you already noted.',
                'items' => [
                    [
                        'id' => 'APIS-2026-004',
                        'title' => 'Equipment Shed Phase 2',
                        'farm' => 'Farm D – Angeles, Pampanga',
                        'type' => 'Building',
                        'requestedBy' => 'Ramon Torres',
                        'requested_at' => '2026-03-01',
                        'action' => 'Noted',
                        'action_key' => 'noted',
                        'acted_at' => '2026-03-10',
                        'current_status' => 'noted',
                        'remarks' => 'Forwarded to ED Manager after VP approval review.',
                    ],
                    [
                        'id' => 'APIS-2026-007',
                        'title' => 'Biogas Plant Construction',
                        'farm' => 'Farm A – Bamban, Tarlac',
                        'type' => 'Infrastructure',
                        'requestedBy' => 'Jose Santos',
                        'requested_at' => '2026-03-20',
                        'action' => 'Rejected Late Filing',
                        'action_key' => 'rejected_late',
                        'acted_at' => '2026-03-21',
                        'current_status' => 'rejected',
                        'remarks' => 'Insufficient justification and incomplete late-filing support documents.',
                    ],
                    [
                        'id' => 'APIS-2026-005',
                        'title' => 'Irrigation Pump Emergency Repair',
                        'farm' => 'Farm B – Capas, Tarlac',
                        'type' => 'Infrastructure',
                        'requestedBy' => 'Maria Cruz',
                        'requested_at' => '2026-03-22',
                        'action' => 'Approved Late Filing',
                        'action_key' => 'approved_late',
                        'acted_at' => '2026-03-23',
                        'current_status' => 'submitted',
                        'remarks' => 'Validated urgency and accepted the justification letter.',
                    ],
                ],
            ],
            'ed_manager' => [
                'label' => 'ED Manager',
                'page_title' => 'History',
                'page_description' => 'Review the requests you already accepted or returned for revision.',
                'items' => [
                    [
                        'id' => 'APIS-2026-006',
                        'title' => 'Biogas Plant Repair',
                        'farm' => 'Farm A – Bamban, Tarlac',
                        'type' => 'Infrastructure',
                        'requestedBy' => 'Jose Santos',
                        'requested_at' => '2026-02-01',
                        'action' => 'Accepted',
                        'action_key' => 'accepted',
                        'acted_at' => '2026-02-14',
                        'current_status' => 'accepted',
                        'remarks' => 'Final acceptance issued after full review of supporting approvals.',
                    ],
                    [
                        'id' => 'APIS-2026-012',
                        'title' => 'Power Room Ventilation Retrofit',
                        'farm' => 'Farm C – Concepcion, Tarlac',
                        'type' => 'Utility',
                        'requestedBy' => 'Pedro Reyes',
                        'requested_at' => '2026-03-02',
                        'action' => 'Returned',
                        'action_key' => 'returned',
                        'acted_at' => '2026-03-08',
                        'current_status' => 'noted',
                        'remarks' => 'Returned for updated meeting notes and revised execution timing.',
                    ],
                    [
                        'id' => 'APIS-2026-013',
                        'title' => 'Water Tank Platform Upgrade',
                        'farm' => 'Farm B – Capas, Tarlac',
                        'type' => 'Infrastructure',
                        'requestedBy' => 'Maria Cruz',
                        'requested_at' => '2026-03-04',
                        'action' => 'Accepted',
                        'action_key' => 'accepted',
                        'acted_at' => '2026-03-11',
                        'current_status' => 'accepted',
                        'remarks' => 'Accepted after confirming all prior approvals and readiness checks.',
                    ],
                ],
            ],
        ];
    }

    public function render()
    {
        abort_unless(array_key_exists($this->role, $this->historyConfig()), 403);

        return view('livewire.history-page')
            ->layout('layouts.app')
            ->layoutData(['title' => $this->pageTitle . ' | EngiStart']);
    }
}
