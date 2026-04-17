<?php

namespace App\Livewire\FarmManager;

use Illuminate\Support\Collection;
use Livewire\Component;

class MyRequestsPage extends Component
{
    public string $filter = 'all';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    protected function loadRequests(): Collection
    {
        return collect($this->placeholderRequestData())
            ->map(fn (array $request): array => $this->mapRequestRecord($request));
    }

    protected function placeholderRequestData(): array
    {
        return [
            [
                'id' => 'APIS-2026-001',
                'title' => 'Poultry House Renovation',
                'needed' => '2026-05-20',
                'submitted' => '2026-03-15',
                'status' => 'submitted',
                'isLate' => false,
                'chain' => [
                    ['role' => 'Farm Manager', 'st' => 'done'],
                    ['role' => 'Division Head', 'st' => 'pending'],
                    ['role' => 'VP Gen Services', 'st' => 'waiting'],
                    ['role' => 'DH Gen Services', 'st' => 'waiting'],
                    ['role' => 'ED Manager', 'st' => 'waiting'],
                ],
            ],
            [
                'id' => 'APIS-2026-006',
                'title' => 'Biogas Plant Repair',
                'needed' => '2026-03-30',
                'submitted' => '2026-02-01',
                'status' => 'accepted',
                'isLate' => false,
                'chain' => [
                    ['role' => 'Farm Manager', 'st' => 'done'],
                    ['role' => 'Division Head', 'st' => 'done'],
                    ['role' => 'VP Gen Services', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'st' => 'done'],
                    ['role' => 'ED Manager', 'st' => 'done'],
                ],
            ],
            [
                'id' => 'APIS-2026-007',
                'title' => 'Biogas Plant Construction',
                'needed' => '2026-04-05',
                'submitted' => '2026-03-20',
                'status' => 'rejected',
                'isLate' => true,
                'chain' => [
                    ['role' => 'Farm Manager', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'st' => 'rejected'],
                ],
            ],
            [
                'id' => 'APIS-2026-008',
                'title' => 'Ventilation Upgrade Request',
                'needed' => '2026-04-18',
                'submitted' => '2026-03-28',
                'status' => 'late_pending',
                'isLate' => true,
                'chain' => [
                    ['role' => 'Farm Manager', 'st' => 'done'],
                    ['role' => 'DH Gen Services', 'st' => 'pending'],
                ],
            ],
        ];
    }

    protected function mapRequestRecord(array $request): array
    {
        return [
            'id' => $request['id'],
            'title' => $request['title'],
            'needed' => $request['needed'],
            'submitted' => $request['submitted'],
            'status' => $request['status'],
            'isLate' => $request['isLate'],
            'chain' => $request['chain'],
        ];
    }

    public function getRequestsProperty(): Collection
    {
        return $this->loadRequests();
    }

    public function getShownRequestsProperty(): Collection
    {
        if ($this->filter === 'all') {
            return $this->requests;
        }

        return $this->requests->where('status', $this->filter)->values();
    }

    public function render()
    {
        return view('livewire.farm-manager.my-requests-page')
            ->layout('layouts.app', [
                'title' => 'My Requests | EngiStart',
                'header' => 'My Requests',
                'subheader' => 'Track the status of your submitted project requests.',
            ]);
    }
}
