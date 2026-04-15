<?php

namespace App\Livewire\DivisionHead;

use Illuminate\Support\Collection;
use Livewire\Component;

class InboxPage extends Component
{
    public ?string $openRequestId = null;

    public ?string $actionMessage = null;

    public string $actionTone = 'info';

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

    public function recommend(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'info';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} was marked as recommended for approval." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
            : 'Dummy action completed.';
    }

    public function reject(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'danger';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} was marked as rejected." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
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
                'id' => 'APIS-2026-001',
                'title' => 'Poultry House Renovation',
                'farm' => 'Farm A – Bamban, Tarlac',
                'type' => 'Building',
                'purpose' => 'Layer capacity expansion',
                'needed' => '2026-05-20',
                'submitted' => '2026-03-15',
                'days' => 58,
                'status' => 'submitted',
                'by' => 'Jose Santos',
                'desc' => 'Full renovation of Building 3 including roof replacement, ventilation system, and lighting upgrades.',
                'chickin' => '2026-06-01',
                'cap' => '25,000 heads',
                'mtgDate' => '2026-03-25',
                'mtgTime' => '10:00',
                'isLate' => false,
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Jose Santos', 'action' => 'Submitted', 'date' => '2026-03-15', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => null, 'action' => 'Recommendation', 'date' => null, 'st' => 'pending'],
                    ['role' => 'VP Gen Services', 'user' => null, 'action' => 'Approval', 'date' => null, 'st' => 'waiting'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Noted', 'date' => null, 'st' => 'waiting'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'waiting'],
                ],
            ],
            [
                'id' => 'APIS-2026-011',
                'title' => 'Feed Conveyor Upgrade',
                'farm' => 'Farm C – Concepcion, Tarlac',
                'type' => 'Equipment',
                'purpose' => 'Improve line transfer efficiency',
                'needed' => '2026-05-30',
                'submitted' => '2026-03-18',
                'days' => 73,
                'status' => 'submitted',
                'by' => 'Pedro Reyes',
                'desc' => 'Upgrade the existing feed conveyor system to reduce transfer delays and improve material handling reliability.',
                'chickin' => null,
                'cap' => '2 transfer lines',
                'mtgDate' => '2026-03-26',
                'mtgTime' => '14:00',
                'isLate' => false,
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Pedro Reyes', 'action' => 'Submitted', 'date' => '2026-03-18', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => null, 'action' => 'Recommendation', 'date' => null, 'st' => 'pending'],
                    ['role' => 'VP Gen Services', 'user' => null, 'action' => 'Approval', 'date' => null, 'st' => 'waiting'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Noted', 'date' => null, 'st' => 'waiting'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'waiting'],
                ],
            ],
            [
                'id' => 'APIS-2026-014',
                'title' => 'Water Line Expansion',
                'farm' => 'Farm B – Capas, Tarlac',
                'type' => 'Infrastructure',
                'purpose' => 'Extend water access to new section',
                'needed' => '2026-06-08',
                'submitted' => '2026-03-22',
                'days' => 78,
                'status' => 'submitted',
                'by' => 'Maria Cruz',
                'desc' => 'Install additional water line routing and support structures for the newly opened production area.',
                'chickin' => null,
                'cap' => '600 meters',
                'mtgDate' => null,
                'mtgTime' => null,
                'isLate' => false,
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Maria Cruz', 'action' => 'Submitted', 'date' => '2026-03-22', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => null, 'action' => 'Recommendation', 'date' => null, 'st' => 'pending'],
                    ['role' => 'VP Gen Services', 'user' => null, 'action' => 'Approval', 'date' => null, 'st' => 'waiting'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Noted', 'date' => null, 'st' => 'waiting'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'waiting'],
                ],
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.division-head.inbox-page')
            ->layout('layouts.app');
    }
}
