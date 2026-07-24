<?php

namespace App\Livewire\EDManager;

use App\Livewire\Concerns\SettingsChangeRequestBase;
use Livewire\Attributes\On;

class SettingsChangeRequestPage extends SettingsChangeRequestBase
{
    protected function confirmEventName(): string
    {
        return 'edSettingsChangeSubmissionConfirmed';
    }

    protected function submittedViaKey(): string
    {
        return 'ed_manager';
    }

    protected function viewName(): string
    {
        return 'livewire.ed-manager.settings-change-request-page';
    }

    #[On('edSettingsChangeSubmissionConfirmed')]
    public function submit(): void
    {
        $this->doSubmit();
    }
}
