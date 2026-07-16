<?php

namespace App\Livewire\FarmManager;

use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssessmentMeetingRequestPage extends Component
{
    public int $projectRequestId;

    public string $requestNumber = '';

    public string $requestTitle = '';

    public bool $submitted = false;

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
            ->where('current_step', 'assessment_meeting_pending')
            ->where('current_owner_id', $user?->id)
            ->whereNull('withdrawn_at')
            ->firstOrFail();

        $this->projectRequestId = $request->id;
        $this->requestNumber = $request->request_number;
        $this->requestTitle = $request->title;
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

        DB::transaction(function () use ($user) {
            $projectRequest = ProjectRequest::query()
                ->whereKey($this->projectRequestId)
                ->where('requestor_id', $user->id)
                ->where('current_step', 'assessment_meeting_pending')
                ->whereNull('withdrawn_at')
                ->firstOrFail();

            $previousStatus = $projectRequest->current_status;
            $previousStep = $projectRequest->current_step;
            $previousOwnerRole = $projectRequest->current_owner_role;

            $isJl = $projectRequest->is_exception_flow;

            $next = $isJl
                ? ['current_step' => 'ed_manager_acceptance', 'current_owner_role' => 'ed_manager']
                : ['current_step' => 'division_head_review', 'current_owner_role' => 'division_head'];

            $projectRequest->fill(array_merge($next, [
                'current_status' => 'submitted',
                'current_owner_id' => null,
                'exception_status' => null,
                'preferred_meeting_date' => $this->form['mtgDate'],
                'preferred_meeting_time' => $this->form['mtgTime'],
                'last_transitioned_at' => now(),
                'latest_remarks' => 'Assessment Meeting Request submitted by requestor.',
            ]));
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => 'assessment_meeting_submitted',
                'from_status' => $previousStatus,
                'to_status' => 'submitted',
                'from_step' => $previousStep,
                'to_step' => $next['current_step'],
                'from_owner_role' => $previousOwnerRole,
                'to_owner_role' => $next['current_owner_role'],
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => $isJl,
                'is_terminal' => false,
                'remarks' => 'Assessment Meeting Request submitted by requestor.',
                'context' => [
                    'review_stage' => 'assessment_meeting_request',
                ],
                'acted_at' => now(),
            ]);
        });

        $this->submitted = true;

        $this->dispatch('notify', type: 'info', message: 'Assessment Meeting Request submitted successfully.');
    }

    public function render()
    {
        return view('livewire.farm-manager.assessment-meeting-request-page')
            ->layout('layouts.app', [
                'title' => 'Assessment Meeting Request | EngiStart',
                'header' => 'Assessment Meeting Request',
                'subheader' => 'Provide your preferred date and time for the assessment meeting.',
            ]);
    }
}
