<?php

namespace App\Livewire\ITAdmin;

use Illuminate\Support\Collection;
use Livewire\Component;

class PendingChangesPage extends Component
{
    public ?string $message = null;

    public function implement(string $requestId): void
    {
        $this->message = "Dummy action: {$requestId} was marked ready for implementation execution.";
    }

    public function getPendingChangesProperty(): Collection
    {
        return collect([
            [
                'id' => 'SCR-2026-004',
                'setting' => 'Required Advance Submission (days)',
                'oldVal' => '45 days',
                'newVal' => '50 days',
                'requestedBy' => 'Engr. D. Baniaga',
                'implementedBy' => '—',
                'status' => 'pending_it',
                'reason' => 'Additional lead time will help align approvals and execution planning.',
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.it-admin.pending-changes-page')->layout('layouts.app');
    }
}
