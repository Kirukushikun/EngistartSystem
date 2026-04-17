<?php

namespace App\Livewire\ITAdmin;

use Illuminate\Support\Collection;
use Livewire\Component;

class UsersPage extends Component
{
    public function getUsersProperty(): Collection
    {
        return collect([
            [
                'name' => 'Jose Santos',
                'email' => 'j.santos@brooksidegroup.org',
                'role' => 'Farm Manager',
                'farm' => 'Farm A – Bamban',
                'status' => 'active',
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.it-admin.users-page')
            ->layout('layouts.app', [
                'title' => 'User Management | EngiStart',
                'header' => 'User Management',
                'subheader' => 'Maintain access and roles for system users.',
            ]);
    }
}
