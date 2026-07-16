<?php

namespace App\Livewire\FarmManager;

use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Support\ProjectTimelineCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class NewRequestPage extends Component
{
    public array $form = [
        'title' => '',
        'type' => '',
        'typeOther' => '',
        'purpose' => '',
        'needed' => '',
        'budgetCategory' => '',
    ];

    public string $timelineAcceptable = '';

    public array $jl = [
        'delayReason' => '',
        'estimatedTurnoverDate' => '',
        'implicationIfNotCompleted' => '',
        'estimatedFinancialOpportunityLoss' => '',
    ];

    public bool $submitted = false;

    public string $submittedId = '';

    public ?int $editingRequestId = null;

    public bool $isEditing = false;

    public function mount(): void
    {
        $editRequestId = request()->integer('edit');

        if ($editRequestId > 0) {
            $this->loadEditableRequest($editRequestId);
        }
    }

    public function updatedTimelineAcceptable(): void
    {
        if ($this->timelineAcceptable === 'yes') {
            $this->jl = [
                'delayReason' => '',
                'estimatedTurnoverDate' => '',
                'implicationIfNotCompleted' => '',
                'estimatedFinancialOpportunityLoss' => '',
            ];
            $this->resetValidation([
                'jl.delayReason',
                'jl.estimatedTurnoverDate',
                'jl.implicationIfNotCompleted',
                'jl.estimatedFinancialOpportunityLoss',
            ]);
        }
    }

    public function getTypeOptionsProperty(): array
    {
        return [
            'production_building' => 'Production Building',
            'support_infrastructure' => 'Support Infrastructure',
            'personnel_facilities' => 'Personnel Facilities',
            'others' => 'Others',
        ];
    }

    public function getBudgetCategoryOptionsProperty(): array
    {
        return ProjectTimelineCalculator::categories();
    }

    public function getComputedTimelineProperty(): ?array
    {
        if ($this->form['budgetCategory'] === '') {
            return null;
        }

        return ProjectTimelineCalculator::forCategory($this->form['budgetCategory']);
    }

    public function openSubmissionReview(): void
    {
        $this->validate($this->rules(), $this->messages());

        $isJl = $this->timelineAcceptable === 'no';
        $timeline = $this->computedTimeline;

        $this->dispatch('openConfirmationModal', config: [
            'title' => $this->isEditing ? 'Review updated request before resubmitting' : 'Review request before submitting',
            'message' => 'Please confirm the summary below. After submission, editing is only allowed until the first reviewer action.',
            'tone' => $isJl ? 'warn' : 'info',
            'confirmText' => $isJl ? 'Submit JL' : ($this->isEditing ? 'Save and resubmit' : 'Confirm and submit'),
            'confirmEvent' => 'requestSubmissionConfirmed',
            'confirmTarget' => self::class,
            'summary' => [
                ['label' => 'Project Description based on CAPEX', 'value' => $this->form['title']],
                ['label' => 'Type', 'value' => $this->form['type'] === 'others' ? $this->form['typeOther'] : ($this->typeOptions[$this->form['type']] ?? '')],
                ['label' => 'Allotted Budget', 'value' => $this->budgetCategoryOptions[$this->form['budgetCategory']] ?? ''],
                ['label' => 'Project Start Date', 'value' => $timeline ? $timeline['start_date']->format('F j, Y') : '—'],
                ['label' => 'Project Completion Date', 'value' => $timeline ? $timeline['completion_date']->format('F j, Y') : '—'],
                ['label' => 'Is the estimated timeline acceptable?', 'value' => $this->timelineAcceptable === 'yes' ? 'Yes' : 'No'],
                ['label' => 'Routing', 'value' => $isJl
                    ? 'Division Head → VP Gen Services (JL review) → Assessment Meeting → ED Manager → DH Gen Services → Engineer'
                    : 'Assessment Meeting → Division Head → VP Gen Services → ED Manager → DH Gen Services → Engineer'],
            ],
        ])->to(ConfirmationModal::class);
    }

    #[On('requestSubmissionConfirmed')]
    public function submit(): void
    {
        $this->validate($this->rules(), $this->messages());

        $user = Auth::user();

        abort_unless($user, 403);

        $isJl = $this->timelineAcceptable === 'no';
        $timeline = $this->computedTimeline;

        $submittedRequest = DB::transaction(function () use ($user, $isJl, $timeline) {
            if ($isJl) {
                $initialStatus = 'jl_pending';
                $initialStep = 'division_head_jl_review';
                $initialOwnerRole = 'division_head';
                $initialOwnerId = null;
            } else {
                $initialStatus = 'submitted';
                $initialStep = 'assessment_meeting_pending';
                $initialOwnerRole = $user->role;
                $initialOwnerId = $user->id;
            }

            $projectRequest = $this->editingRequestId
                ? ProjectRequest::query()
                    ->whereKey($this->editingRequestId)
                    ->where('requestor_id', $user->id)
                    ->whereNull('first_reviewed_at')
                    ->whereNull('locked_at')
                    ->whereNull('withdrawn_at')
                    ->firstOrFail()
                : new ProjectRequest();

            if (! $projectRequest->exists) {
                $nextSequence = (ProjectRequest::max('id') ?? 0) + 1;
                $projectRequest->request_number = sprintf('APIS-%s-%03d', now()->year, $nextSequence);
                $projectRequest->requestor_id = $user->id;
                $projectRequest->requestor_role = $user->role;
                $projectRequest->submitted_at = now();
            }

            $requestType = $this->form['type'] === 'others'
                ? trim($this->form['typeOther'])
                : ($this->typeOptions[$this->form['type']] ?? $this->form['type']);

            $description = $this->form['purpose'] !== '' ? $this->form['purpose'] : $this->form['title'];

            $projectRequest->fill([
                'current_status' => $initialStatus,
                'current_step' => $initialStep,
                'current_owner_role' => $initialOwnerRole,
                'current_owner_id' => $initialOwnerId,
                'is_late' => false,
                'is_exception_flow' => $isJl,
                'exception_status' => $isJl ? 'pending_division_head' : null,
                'title' => $this->form['title'],
                'request_type' => $requestType,
                'budget_category' => $this->form['budgetCategory'],
                'farm_name' => $projectRequest->farm_name ?? $user->farm,
                'purpose' => $this->form['purpose'] ?: null,
                'date_needed' => $this->form['needed'],
                'project_start_date' => $timeline['start_date'] ?? null,
                'project_completion_date' => $timeline['completion_date'] ?? null,
                'description' => $description,
                'locked_at' => null,
                'cancelled_at' => null,
                'withdrawn_at' => null,
                'last_transitioned_at' => now(),
                'latest_remarks' => null,
                'meta' => array_merge($projectRequest->meta ?? [], [
                    'submission_channel' => 'farm_manager_livewire',
                    'last_saved_mode' => $this->isEditing ? 'edit_before_review' : 'new_submission',
                    'timeline_acceptable' => $this->timelineAcceptable,
                    'jl' => $isJl ? $this->jl : null,
                ]),
            ]);
            $projectRequest->save();

            RequestTransition::create([
                'project_request_id' => $projectRequest->id,
                'acted_by_id' => $user->id,
                'acted_by_role' => $user->role,
                'action' => $this->isEditing ? 'resubmitted' : 'submitted',
                'from_status' => $this->isEditing ? $projectRequest->getOriginal('current_status') : null,
                'to_status' => $initialStatus,
                'from_step' => $this->isEditing ? $projectRequest->getOriginal('current_step') : null,
                'to_step' => $initialStep,
                'from_owner_role' => $this->isEditing ? $projectRequest->getOriginal('current_owner_role') : null,
                'to_owner_role' => $initialOwnerRole,
                'to_owner_id' => $initialOwnerId,
                'is_rework' => false,
                'is_exception_path' => $isJl,
                'is_terminal' => false,
                'remarks' => null,
                'context' => [
                    'edited_before_review' => $this->isEditing,
                    'timeline_acceptable' => $this->timelineAcceptable,
                ],
                'acted_at' => now(),
            ]);

            return $projectRequest;
        });

        $wasEditing = $this->isEditing;

        $this->submittedId = $submittedRequest->request_number;
        $this->submitted = true;
        $this->editingRequestId = $submittedRequest->id;
        $this->isEditing = false;

        $this->dispatch('notify',
            type: $isJl ? 'warn' : 'info',
            message: $wasEditing
                ? 'Request updated successfully before reviewer pickup.'
                : ($isJl
                    ? 'Justification Letter submitted and routed to Division Head and VP Gen Services for review.'
                    : 'Request submitted successfully. Please complete the Assessment Meeting Request next.')
        );
    }

    public function resetForm(): void
    {
        $this->reset([
            'form',
            'timelineAcceptable',
            'jl',
            'submitted',
            'submittedId',
            'editingRequestId',
            'isEditing',
        ]);

        $this->form = [
            'title' => '',
            'type' => '',
            'typeOther' => '',
            'purpose' => '',
            'needed' => '',
            'budgetCategory' => '',
        ];

        $this->resetValidation();
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        $rules = [
            'form.title' => ['required', 'string'],
            'form.type' => ['required', 'string'],
            'form.typeOther' => [Rule::requiredIf($this->form['type'] === 'others'), 'nullable', 'string', 'max:255'],
            'form.purpose' => ['nullable', 'string'],
            'form.needed' => ['required', 'date', 'after:today'],
            'form.budgetCategory' => ['required', 'string', 'in:small,medium,large'],
            'timelineAcceptable' => ['required', 'in:yes,no'],
        ];

        if ($this->timelineAcceptable === 'no') {
            $rules['jl.delayReason'] = ['required', 'string'];
            $rules['jl.estimatedTurnoverDate'] = ['required', 'date'];
            $rules['jl.implicationIfNotCompleted'] = ['required', 'string'];
            $rules['jl.estimatedFinancialOpportunityLoss'] = ['required', 'string'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'form.title.required' => 'Project Description based on CAPEX is required.',
            'form.type.required' => 'Type is required.',
            'form.typeOther.required' => 'Please specify the type.',
            'form.needed.required' => 'Date Needed is required.',
            'form.needed.after' => 'Date Needed must be a future date.',
            'form.budgetCategory.required' => 'Allotted Budget is required.',
            'timelineAcceptable.required' => 'Please indicate whether the estimated timeline is acceptable.',
            'jl.delayReason.required' => 'Reason for PIF delay is required.',
            'jl.estimatedTurnoverDate.required' => 'Estimated turnover date is required.',
            'jl.implicationIfNotCompleted.required' => 'Implication if not completed is required.',
            'jl.estimatedFinancialOpportunityLoss.required' => 'Estimated financial opportunity loss is required.',
        ];
    }

    protected function loadEditableRequest(int $requestId): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $projectRequest = ProjectRequest::query()
            ->whereKey($requestId)
            ->where('requestor_id', $user->id)
            ->whereNull('first_reviewed_at')
            ->whereNull('locked_at')
            ->whereNull('withdrawn_at')
            ->first();

        if (! $projectRequest) {
            return;
        }

        $this->editingRequestId = $projectRequest->id;
        $this->isEditing = true;
        $this->submittedId = $projectRequest->request_number;

        $typeKey = array_search($projectRequest->request_type, $this->typeOptions, true);

        $this->form = [
            'title' => $projectRequest->title,
            'type' => $typeKey !== false ? $typeKey : 'others',
            'typeOther' => $typeKey !== false ? '' : (string) $projectRequest->request_type,
            'purpose' => $projectRequest->purpose ?? '',
            'needed' => optional($projectRequest->date_needed)->toDateString() ?? '',
            'budgetCategory' => (string) $projectRequest->budget_category,
        ];

        $this->timelineAcceptable = (string) data_get($projectRequest->meta, 'timeline_acceptable', '');
        $this->jl = data_get($projectRequest->meta, 'jl') ?: [
            'delayReason' => '',
            'estimatedTurnoverDate' => '',
            'implicationIfNotCompleted' => '',
            'estimatedFinancialOpportunityLoss' => '',
        ];
    }

    public function render()
    {
        return view('livewire.farm-manager.new-request-page')
            ->layout('layouts.app', [
                'title' => ($this->isEditing ? 'Edit Request' : 'New Request') . ' | EngiStart',
                'header' => $this->isEditing ? 'Edit Request' : 'New Request',
                'subheader' => $this->isEditing
                    ? 'Update your request before the first reviewer action locks it.'
                    : 'Create and submit a project initialization request.',
            ]);
    }
}
