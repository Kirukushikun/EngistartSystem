<?php

namespace App\Livewire\ITAdmin;

use Illuminate\Support\Collection;
use Livewire\Component;

class AllRequestsPage extends Component
{
    public function getRequestsProperty(): Collection
    {
        return collect([
            [
                'id' => 'APIS-2026-001',
                'title' => 'Poultry House Renovation',
                'farm' => 'Farm A',
                'by' => 'Jose Santos',
                'needed' => '2026-05-20',
                'days' => 58,
                'routing' => 'Standard',
                'status' => 'submitted',
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.it-admin.all-requests-page')
            ->layout('layouts.app', [
                'title' => 'All Requests | EngiStart',
                'header' => 'All Requests',
                'subheader' => 'Monitor request flow across the entire system.',
            ]);
    }
}
