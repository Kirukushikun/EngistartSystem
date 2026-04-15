<?php

namespace App\Livewire\ITAdmin;

use Illuminate\Support\Collection;
use Livewire\Component;

class SettingsPage extends Component
{
    public function getSettingsProperty(): Collection
    {
        return collect([
            ['label' => 'Required Advance Submission (days)', 'key' => 'lead_time_days', 'value' => '45 days'],
            ['label' => 'Small Project Cost Threshold', 'key' => 'small_threshold', 'value' => '₱200,000'],
            ['label' => 'Small Project Lead Time (working days)', 'key' => 'small_lead_time', 'value' => '15 days'],
            ['label' => 'Large Project Lead Time (working days)', 'key' => 'large_lead_time', 'value' => '30 days'],
        ]);
    }

    public function getSystemInformationProperty(): Collection
    {
        return collect([
            ['label' => 'System Name', 'value' => 'EngiStart – Automated Project Initialization System'],
            ['label' => 'Organization', 'value' => 'Brookside Group of Companies'],
            ['label' => 'IT Support Email', 'value' => 'j.montiano@brooksidegroup.org'],
        ]);
    }

    public function render()
    {
        return view('livewire.it-admin.settings-page')->layout('layouts.app');
    }
}
