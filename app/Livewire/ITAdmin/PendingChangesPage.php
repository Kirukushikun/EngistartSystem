<?php

namespace App\Livewire\ITAdmin;

use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Support\SettingsChangeValueFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PendingChangesPage extends Component
{
    public ?string $message = null;

    public function implement(string $requestId): void
    {
        $user = Auth::user();

        abort_unless($user, 403);

        DB::transaction(function () use ($requestId, $user) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('request_type', 'Settings Change')
                ->where('current_owner_role', 'it_admin')
                ->where('current_status', 'pending_it')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $remarks = 'Implemented by IT Admin.';

            $projectRequest->fill([
                'current_status' => 'implemented',
                'current_step' => 'implementation_completed',
                'current_owner_role' => null,
                'current_owner_id' => null,
                'locked_at' => $projectRequest->locked_at ?? now(),
                'last_transitioned_at' => now(),
                'completed_at' => now(),
                'latest_remarks' => $remarks,
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'implemented',
                'from_status' => $previousStatus,
                'to_status' => 'implemented',
                'from_step' => $previousStep,
                'to_step' => 'implementation_completed',
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => null,
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => false,
                'is_terminal' => true,
                'remarks' => $remarks,
                'context' => [
                    'review_stage' => 'it_admin_change_execution',
                    'setting_change' => data_get($projectRequest->meta, 'setting_change', []),
                ],
                'acted_at' => now(),
            ]);
        });

        $this->message = $requestId . ' was marked as implemented.';
    }

    public function getPendingChangesProperty(): Collection
    {
        return ProjectRequest::query()
            ->with('requestor')
            ->where('request_type', 'Settings Change')
            ->where('current_owner_role', 'it_admin')
            ->where('current_status', 'pending_it')
            ->whereNull('withdrawn_at')
            ->orderByDesc('last_transitioned_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProjectRequest $request): array {
                $settingChange = data_get($request->meta, 'setting_change', []);
                $settingKey = data_get($settingChange, 'setting_key');

                return [
                    'id' => $request->request_number,
                    'setting' => data_get($settingChange, 'setting_label', $request->title),
                    'oldVal' => SettingsChangeValueFormatter::format($settingKey, data_get($settingChange, 'current_value', '—')),
                    'newVal' => SettingsChangeValueFormatter::format($settingKey, data_get($settingChange, 'proposed_value', '—')),
                    'requestedBy' => $request->requestor?->name ?? 'Unknown requester',
                    'implementedBy' => '—',
                    'status' => $request->current_status,
                    'reason' => $request->description,
                ];
            })
            ->values();
    }

    public function render()
    {
        return view('livewire.it-admin.pending-changes-page')
            ->layout('layouts.app', [
                'title' => 'Pending Changes | EngiStart',
                'header' => 'Pending Changes',
                'subheader' => 'Implement VP-approved settings changes.',
            ]);
    }
}
