<?php

namespace App\Livewire\ITAdmin;

use Illuminate\Support\Collection;
use Livewire\Component;

class StatusOverridePage extends Component
{
    public array $overrideStatus = [
        'APIS-2026-002' => 'recommended',
    ];

    public ?string $message = null;

    public function applyOverride(string $requestId): void
    {
        $status = $this->overrideStatus[$requestId] ?? 'recommended';
        $this->message = "Dummy action: {$requestId} status override prepared to {$status}.";
    }

    public function getRequestsProperty(): Collection
    {
        return collect([
            [
                'id' => 'APIS-2026-002',
                'title' => 'Feed Storage Expansion',
                'farm' => 'Farm C – Concepcion, Tarlac',
                'status' => 'recommended',
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.it-admin.status-override-page')->layout('layouts.app');
    }
}
