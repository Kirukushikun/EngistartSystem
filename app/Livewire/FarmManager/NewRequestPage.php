<?php

namespace App\Livewire\FarmManager;

use Carbon\Carbon;
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

    public ?int $daysAway = null;

    public bool $isLate = false;

    public bool $isPast = false;

    public $justificationLetter;

    public function mount(): void
    {
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

    public function submit(): void
    {
        $validated = $this->validate($this->rules(), $this->messages());

        if (($validated['form']['needed'] ?? null) !== $this->form['needed']) {
            $this->recalculateNeededDateState();
        }

        $this->submittedId = sprintf('APIS-%s-%03d', now()->year, random_int(1, 999));
        $this->submitted = true;
    }

    public function resetForm(): void
    {
        $this->reset([
            'form',
            'proceed',
            'submitted',
            'submittedId',
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
            $rules['justificationLetter'] = ['required', 'file', 'mimes:pdf,doc,docx'];
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

    public function render()
    {
        return view('livewire.farm-manager.new-request-page')
            ->layout('layouts.app', [
                'title' => 'New Request | EngiStart',
                'header' => 'New Request',
                'subheader' => 'Create and submit a project initialization request.',
            ]);
    }
}
