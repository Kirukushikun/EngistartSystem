<?php

namespace App\Livewire\ITAdmin;

use Illuminate\Support\Collection;
use Livewire\Component;

class AuditTrailPage extends Component
{
    public function getLogsProperty(): Collection
    {
        return collect([
            [
                'ts' => '2026-03-23 09:08',
                'user' => 'Maria Cruz',
                'role' => 'Farm Manager',
                'action' => 'Submitted',
                'id' => 'APIS-2026-005',
                'note' => 'Late filing – 18 days ahead',
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.it-admin.audit-trail-page')->layout('layouts.app');
    }
}
