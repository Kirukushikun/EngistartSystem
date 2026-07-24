<?php

namespace App\Livewire\ITAdmin;

use App\Support\SettingsCatalog;
use Illuminate\Support\Collection;
use Livewire\Component;

class SettingsPage extends Component
{
    public function getSettingsProperty(): Collection
    {
        return collect(SettingsCatalog::optionsWithCurrentValues());
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
        return view('livewire.it-admin.settings-page')
            ->layout('layouts.app', [
                'title' => 'Settings | EngiStart',
                'header' => 'Settings',
                'subheader' => 'Review current system values and control information.',
            ]);
    }
}
