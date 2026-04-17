<?php

namespace App\Livewire\DHGenServices;

use Illuminate\Support\Collection;
use Livewire\Component;

class NotingPage extends Component
{
    public ?string $actionMessage = null;

    public string $actionTone = 'info';

    public array $remarks = [];

    public string $search = '';

    public string $typeFilter = 'all';

    public string $sortBy = 'latest';

    public int $perPage = 5;

    public int $page = 1;

    public function noteForward(string $requestId): void
    {
        $request = $this->loadNotingItems()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'info';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} was noted and forwarded to ED Manager." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
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
        return collect([
            [
                'id' => 'APIS-2026-003',
                'title' => 'Irrigation System Phase 2',
                'farm' => 'Farm B – Capas, Tarlac',
                'type' => 'Infrastructure',
                'purpose' => 'Water supply improvement',
                'needed' => '2026-05-15',
                'submitted' => '2026-03-05',
                'days' => 53,
                'status' => 'vp_approved',
                'by' => 'Maria Cruz',
                'desc' => 'Drip irrigation system installation covering 15 hectares.',
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Maria Cruz', 'action' => 'Submitted', 'date' => '2026-03-05', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => 'Div. Head Santos', 'action' => 'Recommended', 'date' => '2026-03-08', 'st' => 'done'],
                    ['role' => 'VP Gen Services', 'user' => 'Atty. T. Dizon', 'action' => 'Approved', 'date' => '2026-03-12', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Noted', 'date' => null, 'st' => 'pending'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'waiting'],
                ],
            ],
            [
                'id' => 'APIS-2026-021',
                'title' => 'Exhaust Fan Upgrade',
                'farm' => 'Farm D – Angeles, Pampanga',
                'type' => 'Equipment',
                'purpose' => 'Improve ventilation efficiency',
                'needed' => '2026-05-28',
                'submitted' => '2026-03-11',
                'days' => 65,
                'status' => 'vp_approved',
                'by' => 'Ramon Torres',
                'desc' => 'Upgrade of exhaust fans in two poultry houses to address heat buildup and airflow imbalance.',
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Ramon Torres', 'action' => 'Submitted', 'date' => '2026-03-11', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => 'Div. Head Santos', 'action' => 'Recommended', 'date' => '2026-03-14', 'st' => 'done'],
                    ['role' => 'VP Gen Services', 'user' => 'Atty. T. Dizon', 'action' => 'Approved', 'date' => '2026-03-18', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Noted', 'date' => null, 'st' => 'pending'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'waiting'],
                ],
            ],
            [
                'id' => 'APIS-2026-022',
                'title' => 'Substation Fencing Improvement',
                'farm' => 'Farm A – Bamban, Tarlac',
                'type' => 'Utility',
                'purpose' => 'Improve power area safety',
                'needed' => '2026-06-03',
                'submitted' => '2026-03-15',
                'days' => 80,
                'status' => 'vp_approved',
                'by' => 'Jose Santos',
                'desc' => 'Install reinforced safety fencing and access control around the electrical substation area.',
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Jose Santos', 'action' => 'Submitted', 'date' => '2026-03-15', 'st' => 'done'],
                    ['role' => 'Division Head', 'user' => 'Div. Head Santos', 'action' => 'Recommended', 'date' => '2026-03-17', 'st' => 'done'],
                    ['role' => 'VP Gen Services', 'user' => 'Atty. T. Dizon', 'action' => 'Approved', 'date' => '2026-03-20', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Noted', 'date' => null, 'st' => 'pending'],
                    ['role' => 'ED Manager', 'user' => null, 'action' => 'Acceptance', 'date' => null, 'st' => 'waiting'],
                ],
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.dh-gen-services.noting-page')
            ->layout('layouts.app', [
                'title' => 'For Noting | EngiStart',
                'header' => 'For Noting',
                'subheader' => 'Note VP-approved requests and forward them to ED Manager.',
            ]);
    }
}
