@extends('layouts.app')

@section('title', 'Settings Change Request | EngiStart')
@section('header', 'Settings Change Request')
@section('subheader', 'Submit a system-wide settings change request for VP approval.')

@section('content')
<div class="p-6 overflow-y-auto h-full">
    <div class="max-w-[640px]">
        @if ($submitted)
            <div class="flex flex-col items-center text-center gap-3 max-w-[480px] mx-auto mt-10 px-2">
                <div class="w-[52px] h-[52px] rounded-full flex items-center justify-center text-[22px]" style="background: var(--green-bg); color: var(--green)">✓</div>
                <p class="text-[16px] font-medium text-apis-text">Change request submitted</p>
                <p class="text-[12px] text-apis-text2 leading-[1.6]">Your request has been forwarded to VP Gen Services for approval. IT Admin will be notified upon approval to implement the change.</p>
                <div class="w-full rounded-[8px] p-[12px_20px] text-[12px] text-left" style="background: var(--bg2)">
                    <div class="flex justify-between mb-1"><span class="text-apis-text2">Request ID</span><span class="font-mono font-medium text-apis-text">{{ $submittedId }}</span></div>
                    <div class="flex justify-between"><span class="text-apis-text2">Routing</span><span class="font-medium text-[11px] px-2 py-0.5 rounded" style="background: var(--amber-bg); color: var(--amber)">Pending VP Approval</span></div>
                </div>
                <button type="button" wire:click="resetForm" class="rounded-[8px] px-4 py-2 text-[12px] font-medium" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text)">Submit another request</button>
            </div>
        @else
            @include('partials.apis.alert', ['type' => 'warn', 'message' => 'Settings changes affect the entire system. Your request will require VP Gen Services approval before IT Admin can implement it. All changes are logged in the audit trail.'])

            <form wire:submit="submit" class="mt-4 space-y-4">
                @include('partials.apis.section-divider', ['label' => 'Change Request Details'])

                <div class="space-y-4 mt-4">
                    <div>
                        <label class="block text-[12px] text-apis-text mb-1.5">Setting to Change *</label>
                        <select wire:model.live="form.setting" class="apis-form-control @error('form.setting') border-[var(--red)] @enderror">
                            <option value="">Select a setting...</option>
                            @foreach ($this->settingOptions as $option)
                                <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('form.setting')<p class="apis-error-text">{{ $message }}</p>@enderror
                    </div>

                    @if ($this->selectedSetting)
                        <div class="rounded-[8px] p-[12px_14px] text-[12px]" style="background: var(--bg2); border: 0.5px solid var(--border)">
                            <span class="text-apis-text2">Current value:</span>
                            <span class="ml-1 font-medium text-apis-text">{{ $this->selectedSetting['value'] }}</span>
                        </div>
                    @endif

                    <div>
                        <label class="block text-[12px] text-apis-text mb-1.5">Proposed New Value *</label>
                        <input type="text" wire:model.live="form.newValue" class="apis-form-control @error('form.newValue') border-[var(--red)] @enderror" placeholder="Enter the proposed new value...">
                        @error('form.newValue')<p class="apis-error-text">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[12px] text-apis-text mb-1.5">Justification *</label>
                        <textarea wire:model.live="form.reason" class="apis-form-control apis-textarea @error('form.reason') border-[var(--red)] @enderror" placeholder="Explain why this setting should be changed..."></textarea>
                        @error('form.reason')<p class="apis-error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex gap-2 flex-wrap pt-2">
                    <button type="submit" class="rounded-[8px] px-5 py-2 text-[12px] font-medium" style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd)">Submit Change Request</button>
                    <button type="button" wire:click="resetForm" class="rounded-[8px] px-5 py-2 text-[12px] font-medium" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text)">Clear</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
