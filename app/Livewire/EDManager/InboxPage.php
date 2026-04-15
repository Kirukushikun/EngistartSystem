<?php

namespace App\Livewire\EDManager;

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

    public function accept(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'info';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} was marked as accepted." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
            : 'Dummy action completed.';
    }

    public function returnRequest(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'danger';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} was returned by ED Manager." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
            : 'Dummy action completed.';
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
                'id' => 'APIS-2026-004',
                'title' => 'Equipment Shed Phase 2',
                'farm' => 'Farm D – Angeles, Pampanga',
                'type' => 'Building',
                'purpose' => 'Equipment storage expansion',
                'needed' => '2026-05-30',
                'submitted' => '2026-03-01',
                'days' => 68,
                'status' => 'noted',
                'by' => 'Ramon Torres',
                'desc' => 'Secondary shed for tractors and implements, 300 sqm.',
                'chickin' => null,
                'cap' => 'N/A',
                'mtgDate' => '2026-03-08',
                'mtgTime' => '11:00',
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Ramon Torres', 'action' => 'Submitted', 'date' => '2026-03-01', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => 'Div. Head Santos', 'action' => 'Recommended', 'date' => '2026-03-04', 'st' => 'done'],
                    ['role' => 'VP Gen Services', 'user' => 'Atty. T. Dizon', 'action' => 'Approved', 'date' => '2026-03-07', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'user' => 'Ancel Roque', 'action' => 'Noted', 'date' => '2026-03-10', 'st' => 'done'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'pending'],
                ],
            ],
            [
                'id' => 'APIS-2026-023',
                'title' => 'Solar Lighting Expansion',
                'farm' => 'Farm C – Concepcion, Tarlac',
                'type' => 'Utility',
                'purpose' => 'Improve pathway lighting and safety',
                'needed' => '2026-06-06',
                'submitted' => '2026-03-12',
                'days' => 86,
                'status' => 'noted',
                'by' => 'Pedro Reyes',
                'desc' => 'Install additional solar lighting units across service paths and loading zones.',
                'chickin' => null,
                'cap' => '18 lighting poles',
                'mtgDate' => '2026-03-19',
                'mtgTime' => '09:00',
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Pedro Reyes', 'action' => 'Submitted', 'date' => '2026-03-12', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => 'Div. Head Santos', 'action' => 'Recommended', 'date' => '2026-03-15', 'st' => 'done'],
                    ['role' => 'VP Gen Services', 'user' => 'Atty. T. Dizon', 'action' => 'Approved', 'date' => '2026-03-19', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'user' => 'Ancel Roque', 'action' => 'Noted', 'date' => '2026-03-21', 'st' => 'done'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'pending'],
                ],
            ],
            [
                'id' => 'APIS-2026-024',
                'title' => 'Water Refill Station Improvement',
                'farm' => 'Farm A – Bamban, Tarlac',
                'type' => 'Infrastructure',
                'purpose' => 'Improve sanitation and refill capacity',
                'needed' => '2026-05-24',
                'submitted' => '2026-03-09',
                'days' => 76,
                'status' => 'noted',
                'by' => 'Jose Santos',
                'desc' => 'Upgrade the refill station platform, drainage, and dispenser support lines.',
                'chickin' => null,
                'cap' => '2 refill bays',
                'mtgDate' => null,
                'mtgTime' => null,
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Jose Santos', 'action' => 'Submitted', 'date' => '2026-03-09', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => 'Div. Head Santos', 'action' => 'Recommended', 'date' => '2026-03-11', 'st' => 'done'],
                    ['role' => 'VP Gen Services', 'user' => 'Atty. T. Dizon', 'action' => 'Approved', 'date' => '2026-03-14', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'user' => 'Ancel Roque', 'action' => 'Noted', 'date' => '2026-03-16', 'st' => 'done'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'pending'],
                ],
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.ed-manager.inbox-page')
            ->layout('layouts.app');
    }
}
