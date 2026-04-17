<?php

namespace App\Livewire\VPGenServices;

use Illuminate\Support\Collection;
use Livewire\Component;

class InboxPage extends Component
{
    public ?string $actionMessage = null;

    public string $actionTone = 'info';

    public array $remarks = [];

    public string $search = '';

    public string $typeFilter = 'all';

    public string $sortBy = 'latest';

    public int $perPage = 5;

    public int $page = 1;

    public function approve(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'info';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} was marked as approved by VP Gen Services." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
            : 'Dummy action completed.';
    }

    public function reject(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'danger';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} was marked as rejected by VP Gen Services." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
            : 'Dummy action completed.';
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
        return collect([
            [
                'id' => 'APIS-2026-002',
                'title' => 'Feed Storage Expansion',
                'farm' => 'Farm C – Concepcion, Tarlac',
                'type' => 'Building',
                'purpose' => 'Additional feed warehouse',
                'needed' => '2026-06-01',
                'submitted' => '2026-03-10',
                'days' => 70,
                'status' => 'recommended',
                'by' => 'Pedro Reyes',
                'desc' => 'Construction of 500 sqm feed storage building adjacent to existing facility.',
                'chickin' => null,
                'cap' => '500 MT',
                'mtgDate' => '2026-03-20',
                'mtgTime' => '14:00',
                'isLate' => false,
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Pedro Reyes', 'action' => 'Submitted', 'date' => '2026-03-10', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => 'Div. Head Santos', 'action' => 'Recommended', 'date' => '2026-03-14', 'st' => 'done'],
                    ['role' => 'VP Gen Services', 'user' => null, 'action' => 'Approval', 'date' => null, 'st' => 'pending'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Noted', 'date' => null, 'st' => 'waiting'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'waiting'],
                ],
            ],
            [
                'id' => 'APIS-2026-015',
                'title' => 'Boiler Control Panel Upgrade',
                'farm' => 'Farm D – Angeles, Pampanga',
                'type' => 'Equipment',
                'purpose' => 'Improve environmental control stability',
                'needed' => '2026-05-25',
                'submitted' => '2026-03-16',
                'days' => 69,
                'status' => 'recommended',
                'by' => 'Ramon Torres',
                'desc' => 'Replace aging control panels and relays to reduce outages and improve response accuracy.',
                'chickin' => null,
                'cap' => '3 panel zones',
                'mtgDate' => '2026-03-24',
                'mtgTime' => '09:30',
                'isLate' => false,
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Ramon Torres', 'action' => 'Submitted', 'date' => '2026-03-16', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => 'Div. Head Santos', 'action' => 'Recommended', 'date' => '2026-03-19', 'st' => 'done'],
                    ['role' => 'VP Gen Services', 'user' => null, 'action' => 'Approval', 'date' => null, 'st' => 'pending'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Noted', 'date' => null, 'st' => 'waiting'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'waiting'],
                ],
            ],
            [
                'id' => 'APIS-2026-018',
                'title' => 'Generator Housing Extension',
                'farm' => 'Farm A – Bamban, Tarlac',
                'type' => 'Infrastructure',
                'purpose' => 'Weather protection for backup line',
                'needed' => '2026-06-12',
                'submitted' => '2026-03-21',
                'days' => 83,
                'status' => 'recommended',
                'by' => 'Jose Santos',
                'desc' => 'Extend the housing structure to cover the secondary generator connection and maintenance access path.',
                'chickin' => null,
                'cap' => '120 sqm',
                'mtgDate' => null,
                'mtgTime' => null,
                'isLate' => false,
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Jose Santos', 'action' => 'Submitted', 'date' => '2026-03-21', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => 'Div. Head Santos', 'action' => 'Recommended', 'date' => '2026-03-23', 'st' => 'done'],
                    ['role' => 'VP Gen Services', 'user' => null, 'action' => 'Approval', 'date' => null, 'st' => 'pending'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Noted', 'date' => null, 'st' => 'waiting'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'waiting'],
                ],
            ],
        ]);
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
