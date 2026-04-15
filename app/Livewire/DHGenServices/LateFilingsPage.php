<?php

namespace App\Livewire\DHGenServices;

use Illuminate\Support\Collection;
use Livewire\Component;

class LateFilingsPage extends Component
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
        $request = $this->loadLateFilings()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'warn';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} late filing was approved for workflow continuation." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
            : 'Dummy action completed.';
    }

    public function reject(string $requestId): void
    {
        $request = $this->loadLateFilings()->firstWhere('id', $requestId);
        $remarks = trim($this->remarks[$requestId] ?? '');

        $this->actionTone = 'danger';
        $this->actionMessage = $request
            ? "Dummy action: {$request['id']} late filing was rejected." . ($remarks !== '' ? " Remarks: {$remarks}" : '')
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
        return $this->loadLateFilings();
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

    protected function loadLateFilings(): Collection
    {
        return collect([
            [
                'id' => 'APIS-2026-005',
                'title' => 'Irrigation Pump Emergency Repair',
                'farm' => 'Farm B – Capas, Tarlac',
                'type' => 'Infrastructure',
                'purpose' => 'Emergency pump replacement',
                'needed' => '2026-04-10',
                'submitted' => '2026-03-22',
                'days' => 18,
                'status' => 'late_pending',
                'by' => 'Maria Cruz',
                'desc' => 'Urgent replacement of main irrigation pump.',
                'jl' => 'JL_APIS-2026-005.pdf',
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Maria Cruz', 'action' => 'Submitted (Late Filing)', 'date' => '2026-03-22', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Validation / Decision', 'date' => null, 'st' => 'pending'],
                ],
            ],
            [
                'id' => 'APIS-2026-019',
                'title' => 'Brooder Heater Replacement',
                'farm' => 'Farm C – Concepcion, Tarlac',
                'type' => 'Equipment',
                'purpose' => 'Prevent temperature loss in brooder section',
                'needed' => '2026-04-06',
                'submitted' => '2026-03-24',
                'days' => 13,
                'status' => 'late_pending',
                'by' => 'Pedro Reyes',
                'desc' => 'Replacement of failed brooder heaters affecting chick survival rates.',
                'jl' => 'JL_APIS-2026-019.pdf',
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Pedro Reyes', 'action' => 'Submitted (Late Filing)', 'date' => '2026-03-24', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Validation / Decision', 'date' => null, 'st' => 'pending'],
                ],
            ],
            [
                'id' => 'APIS-2026-020',
                'title' => 'Water Tank Leak Rectification',
                'farm' => 'Farm A – Bamban, Tarlac',
                'type' => 'Utility',
                'purpose' => 'Prevent supply interruption',
                'needed' => '2026-04-08',
                'submitted' => '2026-03-25',
                'days' => 14,
                'status' => 'late_pending',
                'by' => 'Jose Santos',
                'desc' => 'Immediate patching and reinforcement of the elevated water tank line due to rapid leakage.',
                'jl' => 'JL_APIS-2026-020.pdf',
                'chain' => [
                    ['role' => 'Farm Manager', 'user' => 'Jose Santos', 'action' => 'Submitted (Late Filing)', 'date' => '2026-03-25', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'user' => null, 'action' => 'Validation / Decision', 'date' => null, 'st' => 'pending'],
                ],
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.dh-gen-services.late-filings-page')->layout('layouts.app');
    }
}
