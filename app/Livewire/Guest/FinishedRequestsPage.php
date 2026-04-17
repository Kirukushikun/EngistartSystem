<?php

namespace App\Livewire\Guest;

use Illuminate\Support\Collection;
use Livewire\Component;

class FinishedRequestsPage extends Component
{
    public string $search = '';

    public string $statusFilter = 'all';

    public function getFinishedRequestsProperty(): Collection
    {
        $items = collect([
            [
                'id' => 'APIS-2026-006',
                'title' => 'Biogas Plant Repair',
                'farm' => 'Farm A – Bamban, Tarlac',
                'by' => 'Jose Santos',
                'needed' => '2026-03-30',
                'completedAt' => '2026-02-14',
                'status' => 'accepted',
                'type' => 'Infrastructure',
                'purpose' => 'Restore biogas production',
                'desc' => 'Full repair of biogas plant unit 2 to restore normal gas capture and energy conversion operations.',
                'cap' => 'N/A',
                'chickin' => null,
                'mtgDate' => '2026-02-08',
                'mtgTime' => '10:00',
            ],
            [
                'id' => 'APIS-2026-007',
                'title' => 'Biogas Plant Construction',
                'farm' => 'Farm A – Bamban, Tarlac',
                'by' => 'Jose Santos',
                'needed' => '2026-04-05',
                'completedAt' => '2026-03-21',
                'status' => 'rejected',
                'type' => 'Infrastructure',
                'purpose' => 'Expand biogas capacity',
                'desc' => 'Construction of a new biogas plant adjacent to the existing unit to improve waste-to-energy output and support future capacity growth.',
                'cap' => 'N/A',
                'chickin' => null,
                'mtgDate' => null,
                'mtgTime' => null,
            ],
        ]);

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $items = $items->filter(function (array $request) use ($needle): bool {
                return str_contains(mb_strtolower($request['id']), $needle)
                    || str_contains(mb_strtolower($request['title']), $needle)
                    || str_contains(mb_strtolower($request['farm']), $needle)
                    || str_contains(mb_strtolower($request['by']), $needle);
            })->values();
        }

        if ($this->statusFilter !== 'all') {
            $items = $items->where('status', $this->statusFilter)->values();
        }

        return $items->sortByDesc('completedAt')->values();
    }

    public function render()
    {
        return view('livewire.guest.finished-requests-page')
            ->layout('layouts.app', [
                'title' => 'Finished Requests | EngiStart',
                'header' => 'Finished Requests',
                'subheader' => 'View completed request outcomes only. In-progress requests are not visible here.',
            ]);
    }
}
