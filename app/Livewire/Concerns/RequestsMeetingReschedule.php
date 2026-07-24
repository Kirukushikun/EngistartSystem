<?php

namespace App\Livewire\Concerns;

use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Support\WorkflowNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait RequestsMeetingReschedule
{
    public function confirmReschedule(string $requestId): void
    {
        $request = $this->loadInboxItems()->firstWhere('id', $requestId);

        if (! $request || ! $request['isPendingHere']) {
            return;
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => 'Return for reschedule?',
            'message' => 'Send ' . $request['id'] . ' back to the requestor to propose a different meeting date/time? This does not reject the request.',
            'tone' => 'warn',
            'confirmText' => 'Return for Reschedule',
            'confirmEvent' => $this->rescheduleConfirmEventName(),
            'confirmTarget' => static::class,
            'payload' => ['requestId' => $requestId],
        ])->to(ConfirmationModal::class);
    }

    protected function performReschedule(string $requestId, string $ownerRoleGuard): void
    {
        $user = Auth::user();
        $remarks = trim($this->remarks[$requestId] ?? '');

        abort_unless($user, 403);

        $projectRequest = DB::transaction(function () use ($requestId, $remarks, $user, $ownerRoleGuard) {
            $projectRequest = ProjectRequest::query()
                ->where('request_number', $requestId)
                ->where('current_owner_role', $ownerRoleGuard)
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;
            $defaultRemarks = 'Reschedule requested by ' . $this->rescheduleRoleLabel() . '.';

            $projectRequest->fill([
                'current_status' => 'reschedule_requested',
                'current_step' => 'requestor_reschedule',
                'current_owner_role' => $projectRequest->requestor_role,
                'current_owner_id' => $projectRequest->requestor_id,
                'first_reviewed_at' => $projectRequest->first_reviewed_at ?? now(),
                'locked_at' => $projectRequest->locked_at ?? now(),
                'last_transitioned_at' => now(),
                'latest_remarks' => $remarks !== '' ? $remarks : $defaultRemarks,
                'meta' => array_merge($projectRequest->meta ?? [], [
                    'reschedule_return' => [
                        'status' => $previousStatus,
                        'step' => $previousStep,
                        'owner_role' => $previousOwnerRole,
                    ],
                ]),
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'reschedule_requested',
                'from_status' => $previousStatus,
                'to_status' => 'reschedule_requested',
                'from_step' => $previousStep,
                'to_step' => 'requestor_reschedule',
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => $projectRequest->requestor_role,
                'to_owner_id' => $projectRequest->requestor_id,
                'is_rework' => true,
                'is_exception_path' => $projectRequest->is_late,
                'is_terminal' => false,
                'remarks' => $remarks !== '' ? $remarks : $defaultRemarks,
                'context' => [
                    'review_stage' => $ownerRoleGuard,
                ],
                'acted_at' => now(),
            ]);

            return $projectRequest;
        });

        WorkflowNotifier::notifyOwner(
            $projectRequest,
            'reschedule_requested',
            'Please Propose a New Meeting Schedule',
            $projectRequest->request_number . ' — ' . $projectRequest->title . ' needs a new meeting date/time before ' . $this->rescheduleRoleLabel() . ' can proceed.'
        );

        unset($this->remarks[$requestId]);

        $this->dispatch('notify', type: 'warn', message: $requestId . ' was sent back for a new meeting schedule.');
    }

    abstract protected function rescheduleConfirmEventName(): string;

    abstract protected function rescheduleRoleLabel(): string;
}
