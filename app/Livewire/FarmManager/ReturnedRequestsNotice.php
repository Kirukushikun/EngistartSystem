<?php

namespace App\Livewire\FarmManager;

use App\Models\ProjectRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ReturnedRequestsNotice extends Component
{
    public array $notices = [];

    public function mount(): void
    {
        $this->loadNotices();
    }

    protected function loadNotices(): void
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'farm_manager') {
            $this->notices = [];

            return;
        }

        $this->notices = ProjectRequest::query()
            ->where('requestor_id', $user->id)
            ->whereIn('current_status', ['returned_to_requestor', 'rejected'])
            ->whereNull('requestor_notice_seen_at')
            ->whereNull('withdrawn_at')
            ->orderByDesc('last_transitioned_at')
            ->get()
            ->map(fn (ProjectRequest $request): array => [
                'id' => $request->id,
                'code' => $request->request_number,
                'title' => $request->title,
                'statusLabel' => ProjectRequest::statusLabel($request->current_status),
                'isRejected' => $request->current_status === 'rejected',
                'remarks' => $request->latest_remarks ?: 'No remarks provided.',
            ])
            ->values()
            ->all();
    }

    public function dismiss(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $ids = collect($this->notices)->pluck('id')->all();

        if ($ids !== []) {
            ProjectRequest::query()
                ->whereIn('id', $ids)
                ->where('requestor_id', $user->id)
                ->update(['requestor_notice_seen_at' => now()]);
        }

        $this->notices = [];
    }

    public function reviewAndDismiss()
    {
        $this->dismiss();

        return $this->redirect(route('farm-manager.requests.index'));
    }

    public function render()
    {
        return view('livewire.farm-manager.returned-requests-notice');
    }
}
