<?php

namespace App\Livewire\DHGenServices;

use App\Livewire\Concerns\SettingsChangeRequestBase;
use Livewire\Attributes\On;

class SettingsChangeRequestPage extends SettingsChangeRequestBase
{
    protected function confirmEventName(): string
    {
        return 'dhSettingsChangeSubmissionConfirmed';
    }

    protected function submittedViaKey(): string
    {
        return 'dh_gen_services';
    }

    protected function viewName(): string
    {
        return 'livewire.dh-gen-services.settings-change-request-page';
    }

    #[On('dhSettingsChangeSubmissionConfirmed')]
    public function submit(): void
    {
        $this->doSubmit();
    }
}
