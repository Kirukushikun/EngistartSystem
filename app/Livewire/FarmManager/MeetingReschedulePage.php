<?php

namespace App\Livewire\FarmManager;

use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Support\WorkflowNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MeetingReschedulePage extends Component
{
    public int $projectRequestId;

    public string $requestNumber = '';

    public string $requestTitle = '';

    public bool $submitted = false;

    public bool $isFirstTimeSchedule = false;

    public array $form = [
        'mtgDate' => '',
        'mtgTime' => '',
    ];

    public function mount(int $projectRequest): void
    {
        $user = Auth::user();

        $request = ProjectRequest::query()
            ->whereKey($projectRequest)
            ->where('requestor_id', $user?->id)
            ->whereIn('current_step', ['requestor_reschedule', 'requestor_meeting_schedule'])
            ->where('current_owner_id', $user?->id)
            ->whereNull('withdrawn_at')
            ->firstOrFail();

        $this->projectRequestId = $request->id;
        $this->requestNumber = $request->request_number;
        $this->requestTitle = $request->title;
        $this->isFirstTimeSchedule = $request->current_step === 'requestor_meeting_schedule';
        $this->form['mtgDate'] = optional($request->preferred_meeting_date)->toDateString() ?? '';
        $this->form['mtgTime'] = (string) ($request->preferred_meeting_time ?? '');
    }

    protected function rules(): array
    {
        return [
            'form.mtgDate' => ['required', 'date', 'after:today'],
            'form.mtgTime' => ['required'],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $user = Auth::user();

        abort_unless($user, 403);

        $projectRequest = DB::transaction(function () use ($user) {
            $projectRequest = ProjectRequest::query()
                ->whereKey($this->projectRequestId)
                ->where('requestor_id', $user->id)
                ->whereIn('current_step', ['requestor_reschedule', 'requestor_meeting_schedule'])
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $isFirstTimeSchedule = $projectRequest->current_step === 'requestor_meeting_schedule';

            $meta = $projectRequest->meta ?? [];

            if ($isFirstTimeSchedule) {
                $target = [
                    'status' => 'jl_meeting_review',
                    'step' => 'division_head_meeting_review',
                    'owner_role' => 'division_head',
                ];
            } else {
                $finalReturnTo = data_get($projectRequest->meta, 'reschedule_return');

                abort_if(! $finalReturnTo, 422, 'This request has no reviewer to return to.');

                unset($meta['reschedule_return']);

                if ($finalReturnTo['owner_role'] === 'division_head') {
                    // Division Head requested the reschedule themselves, so the new
                    // schedule goes straight back into their own queue.
                    $target = $finalReturnTo;
                } else {
                    // Every other role's reschedule request must still pass through
                    // Division Head approval before continuing to its real destination,
                    // which is stashed here for the DH's approve action to pick up.
                    $meta['reschedule_final_return'] = $finalReturnTo;
                    $target = [
                        'status' => 'reschedule_meeting_review',
                        'step' => 'division_head_reschedule_review',
                        'owner_role' => 'division_head',
                    ];
                }
            }

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $projectRequest->fill([
                'current_status' => $target['status'],
                'current_step' => $target['step'],
                'current_owner_role' => $target['owner_role'],
                'current_owner_id' => null,
                'preferred_meeting_date' => $this->form['mtgDate'],
                'preferred_meeting_time' => $this->form['mtgTime'],
                'last_transitioned_at' => now(),
                'latest_remarks' => 'New meeting schedule submitted by requestor.',
                'meta' => $meta,
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => $isFirstTimeSchedule ? 'meeting_schedule_submitted' : 'reschedule_submitted',
                'from_status' => $previousStatus,
                'to_status' => $target['status'],
                'from_step' => $previousStep,
                'to_step' => $target['step'],
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => $target['owner_role'],
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => $projectRequest->is_late,
                'is_terminal' => false,
                'remarks' => 'New meeting schedule submitted by requestor.',
                'context' => [
                    'review_stage' => $previousStep,
                ],
                'acted_at' => now(),
            ]);

            return $projectRequest;
        });

        WorkflowNotifier::notifyOwner(
            $projectRequest,
            'reschedule_submitted',
            'New Meeting Schedule Proposed',
            $projectRequest->request_number . ' — ' . $projectRequest->title . ' has a new proposed meeting date/time for your review.'
        );

        $this->submitted = true;

        $this->dispatch('notify', type: 'info', message: 'New meeting schedule submitted successfully.');
    }

    public function render()
    {
        return view('livewire.farm-manager.meeting-reschedule-page')
            ->layout('layouts.app', [
                'title' => 'Update Meeting Schedule | Project Initialization System',
                'header' => 'Update Meeting Schedule',
                'subheader' => 'Set or update the meeting date/time before the reviewer can proceed.',
            ]);
    }
}
