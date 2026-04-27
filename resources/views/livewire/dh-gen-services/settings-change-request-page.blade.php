<div class="p-6 overflow-y-auto h-full">
    @if ($submitted)
        <div class="flex min-h-full w-full items-center justify-center">
            @include('partials.apis.settings-change-success-state', ['submittedId' => $submittedId])
        </div>
    @else
        <div class="max-w-[640px]">
            @include('partials.apis.alert', ['type' => 'warn', 'message' => 'Settings changes affect the entire system. Your request will require VP Gen Services approval before IT Admin can implement it. All changes are logged in the audit trail.'])

            <form wire:submit="openSubmissionReview" class="mt-4 space-y-4">
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
                            <span class="text-apis-text2">Current system value:</span>
                            <span class="ml-1 font-medium text-apis-text">{{ $this->selectedSetting['value'] }}</span>
                        </div>
                    @endif

                    <div>
                        <label class="block text-[12px] text-apis-text mb-1.5">Proposed New Value *</label>
                        <input type="text" wire:model.blur="form.newValue" class="apis-form-control @error('form.newValue') border-[var(--red)] @enderror" placeholder="Enter the proposed new value...">
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
        </div>
    @endif
</div>
