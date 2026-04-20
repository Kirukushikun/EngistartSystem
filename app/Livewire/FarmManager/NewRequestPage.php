<?php

namespace App\Livewire\FarmManager;

use App\Livewire\Shared\ConfirmationModal;
use App\Models\ProjectRequest;
use App\Models\RequestAttachment;
use App\Models\RequestTransition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class NewRequestPage extends Component
{
    use WithFileUploads;

    public array $form = [
        'title' => '',
        'type' => '',
        'purpose' => '',
        'needed' => '',
        'desc' => '',
        'chickin' => '',
        'cap' => '',
        'mtgDate' => '',
        'mtgTime' => '',
    ];

    public bool $proceed = false;

    public bool $submitted = false;

    public string $submittedId = '';

    public ?int $editingRequestId = null;

    public bool $isEditing = false;

    public bool $hasExistingJustificationLetter = false;

    public ?int $daysAway = null;

    public bool $isLate = false;

    public bool $isPast = false;

    public $justificationLetter;

    public function mount(): void
    {
        $editRequestId = request()->integer('edit');

        if ($editRequestId > 0) {
            $this->loadEditableRequest($editRequestId);
        }

        $this->recalculateNeededDateState();
    }

    public function updatedFormNeeded(): void
    {
        $this->recalculateNeededDateState();
    }

    public function updatedProceed(): void
    {
        if ($this->proceed) {
            $this->resetValidation('proceed');
        }
    }

    public function openSubmissionReview(): void
    {
        $validated = $this->validate($this->rules(), $this->messages());

        if (($validated['form']['needed'] ?? null) !== $this->form['needed']) {
            $this->recalculateNeededDateState();
        }

        $this->dispatch('openConfirmationModal', config: [
            'title' => $this->isEditing ? 'Review updated request before resubmitting' : 'Review request before submitting',
            'message' => 'Please confirm the summary below. After submission, editing is only allowed until the first reviewer action.',
            'tone' => $this->isLate ? 'warn' : 'info',
            'confirmText' => $this->isEditing ? 'Save and resubmit' : 'Confirm and submit',
            'confirmEvent' => 'requestSubmissionConfirmed',
            'confirmTarget' => self::class,
            'summary' => [
                ['label' => 'Project Title', 'value' => $this->form['title']],
                ['label' => 'Type', 'value' => $this->form['type']],
                ['label' => 'Date Needed', 'value' => Carbon::parse($this->form['needed'])->format('F j, Y')],
                ['label' => 'Routing', 'value' => $this->isLate ? 'Late Filing → DH Gen Services' : 'Standard Workflow → Division Head'],
                ['label' => 'Late Filing', 'value' => $this->isLate ? 'Yes' : 'No'],
                ['label' => 'Justification Letter', 'value' => $this->isLate ? ($this->justificationLetter ? 'Attached for this submission' : ($this->hasExistingJustificationLetter ? 'Existing attachment retained' : 'Required')) : 'Not required'],
            ],
        ])->to(ConfirmationModal::class);
    }

    #[On('requestSubmissionConfirmed')]
    public function submit(): void
    {
        $validated = $this->validate($this->rules(), $this->messages());

        if (($validated['form']['needed'] ?? null) !== $this->form['needed']) {
            $this->recalculateNeededDateState();
        }

        $user = Auth::user();

        abort_unless($user, 403);

        $submittedRequest = DB::transaction(function () use ($user) {
            $initialStatus = $this->isLate ? 'late_pending' : 'submitted';
            $initialStep = $this->isLate ? 'dh_gen_late_review' : 'division_head_review';
            $initialOwnerRole = $this->isLate ? 'dh_gen_services' : 'division_head';

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

            $projectRequest->fill([
                'current_status' => $initialStatus,
                'current_step' => $initialStep,
                'current_owner_role' => $initialOwnerRole,
                'current_owner_id' => null,
                'is_late' => $this->isLate,
                'is_exception_flow' => false,
                'exception_status' => null,
                'title' => $this->form['title'],
                'request_type' => $this->form['type'],
                'farm_name' => null,
                'purpose' => $this->form['purpose'] ?: null,
                'date_needed' => $this->form['needed'],
                'chick_in_date' => $this->form['chickin'] ?: null,
                'capacity' => $this->form['cap'] ?: null,
                'description' => $this->form['desc'],
                'preferred_meeting_date' => $this->form['mtgDate'] ?: null,
                'preferred_meeting_time' => $this->form['mtgTime'] ?: null,
                'locked_at' => null,
                'cancelled_at' => null,
                'withdrawn_at' => null,
                'last_transitioned_at' => now(),
                'latest_remarks' => null,
                'meta' => array_merge($projectRequest->meta ?? [], [
                    'days_away' => $this->daysAway,
                    'submission_channel' => 'farm_manager_livewire',
                    'last_saved_mode' => $this->isEditing ? 'edit_before_review' : 'new_submission',
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
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => $this->isLate,
                'is_terminal' => false,
                'remarks' => null,
                'context' => [
                    'is_late' => $this->isLate,
                    'days_away' => $this->daysAway,
                    'edited_before_review' => $this->isEditing,
                ],
                'acted_at' => now(),
            ]);

            if ($this->isLate && $this->justificationLetter) {
                $storedPath = $this->justificationLetter->store('request-attachments', 'public');

                RequestAttachment::create([
                    'project_request_id' => $projectRequest->id,
                    'uploaded_by_id' => $user->id,
                    'attachment_type' => 'justification_letter',
                    'original_name' => $this->justificationLetter->getClientOriginalName(),
                    'disk' => 'public',
                    'path' => $storedPath,
                    'mime_type' => $this->justificationLetter->getClientMimeType(),
                    'size_bytes' => $this->justificationLetter->getSize(),
                    'is_active' => true,
                    'meta' => [
                        'required_for_late_filing' => true,
                    ],
                    'uploaded_at' => now(),
                ]);
            }

            return $projectRequest;
        });

        $wasEditing = $this->isEditing;

        $this->submittedId = $submittedRequest->request_number;
        $this->submitted = true;
        $this->editingRequestId = $submittedRequest->id;
        $this->isEditing = false;
        $this->hasExistingJustificationLetter = $submittedRequest->attachments()->where('attachment_type', 'justification_letter')->where('is_active', true)->exists();

        $this->dispatch('notify',
            type: $this->isLate ? 'warn' : 'info',
            message: $wasEditing
                ? 'Request updated successfully before reviewer pickup.'
                : ($this->isLate
                    ? 'Late filing submitted and routed to DH Gen Services for review.'
                    : 'Request submitted successfully and routed to Division Head.')
        );
    }

    public function resetForm(): void
    {
        $this->reset([
            'form',
            'proceed',
            'submitted',
            'submittedId',
            'editingRequestId',
            'isEditing',
            'hasExistingJustificationLetter',
            'daysAway',
            'isLate',
            'isPast',
            'justificationLetter',
        ]);

        $this->form = [
            'title' => '',
            'type' => '',
            'purpose' => '',
            'needed' => '',
            'desc' => '',
            'chickin' => '',
            'cap' => '',
            'mtgDate' => '',
            'mtgTime' => '',
        ];

        $this->resetValidation();
        $this->resetErrorBag();
        $this->recalculateNeededDateState();
    }

    protected function recalculateNeededDateState(): void
    {
        if (blank($this->form['needed'])) {
            $this->daysAway = null;
            $this->isLate = false;
            $this->isPast = false;

            return;
        }

        $today = Carbon::today();
        $needed = Carbon::parse($this->form['needed']);

        $this->daysAway = $today->diffInDays($needed, false);
        $this->isPast = $this->daysAway < 0;
        $this->isLate = $this->daysAway >= 0 && $this->daysAway < 45;
    }

    protected function rules(): array
    {
        $rules = [
            'form.title' => ['required', 'string'],
            'form.type' => ['required', 'string'],
            'form.purpose' => ['nullable', 'string'],
            'form.needed' => ['required', 'date', 'after:today'],
            'form.desc' => ['required', 'string'],
            'form.chickin' => ['nullable', 'date'],
            'form.cap' => ['nullable', 'string'],
            'form.mtgDate' => ['nullable', 'date'],
            'form.mtgTime' => ['nullable'],
        ];

        if ($this->isLate) {
            $rules['proceed'] = ['accepted'];

            if (! $this->hasExistingJustificationLetter || $this->justificationLetter) {
                $rules['justificationLetter'] = ['required', 'file', 'mimes:pdf,doc,docx'];
            }
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'form.title.required' => 'Project Title is required.',
            'form.type.required' => 'Type is required.',
            'form.needed.required' => 'Date Needed is required.',
            'form.needed.after' => 'Date Needed must be a future date.',
            'form.desc.required' => 'Detailed Description is required.',
            'proceed.accepted' => 'Please acknowledge the late filing requirement.',
            'justificationLetter.required' => 'The Justification Letter is required for late filings.',
            'justificationLetter.mimes' => 'The Justification Letter must be a PDF, DOC, or DOCX file.',
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
        $this->hasExistingJustificationLetter = $projectRequest->attachments()
            ->where('attachment_type', 'justification_letter')
            ->where('is_active', true)
            ->exists();
        $this->form = [
            'title' => $projectRequest->title,
            'type' => $projectRequest->request_type,
            'purpose' => $projectRequest->purpose ?? '',
            'needed' => optional($projectRequest->date_needed)->toDateString() ?? '',
            'desc' => $projectRequest->description,
            'chickin' => optional($projectRequest->chick_in_date)->toDateString() ?? '',
            'cap' => $projectRequest->capacity ?? '',
            'mtgDate' => optional($projectRequest->preferred_meeting_date)->toDateString() ?? '',
            'mtgTime' => $projectRequest->preferred_meeting_time ?? '',
        ];
        $this->proceed = $projectRequest->is_late;
        $this->isLate = $projectRequest->is_late;
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
