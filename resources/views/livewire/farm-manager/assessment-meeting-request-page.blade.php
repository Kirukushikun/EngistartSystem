<div class="p-6 overflow-y-auto h-full">
    <div class="max-w-[560px] @if($submitted) mx-auto min-h-full flex items-center justify-center @endif">
        @if ($submitted)
            <div class="flex w-full flex-col items-center justify-center text-center gap-3 max-w-[480px] px-2">
                <div class="w-[52px] h-[52px] rounded-full flex items-center justify-center text-[22px]"
                     style="background: var(--green-bg); color: var(--green)">✓</div>
                <p class="text-[16px] font-medium text-apis-text">Assessment Meeting Request submitted</p>
                <p class="text-[12px] text-apis-text2 leading-[1.6]">{{ $requestNumber }} is now in the standard approval workflow.</p>
                <a href="{{ route('farm-manager.requests.index') }}"
                   class="mt-1 text-xs text-apis-text2 px-4 py-2 rounded transition-colors hover:bg-apis-bg2"
                   style="border: 0.5px solid var(--border2)">
                    Back to My Requests
                </a>
            </div>
        @else
            <form wire:submit="submit" class="contents">
                <div class="rounded-[8px] p-[11px_16px] mb-4" style="background: var(--bg2); border: 0.5px solid var(--border)">
                    <div class="text-[12px]"><span class="text-apis-text2">Request: </span><span class="font-mono font-medium">{{ $requestNumber }}</span></div>
                    <div class="text-[12px] mt-1"><span class="text-apis-text2">Description: </span><span class="font-medium">{{ $requestTitle }}</span></div>
                </div>

                @include('partials.apis.alert', [
                    'type' => 'info',
                    'message' => 'We kindly request your availability for a meeting to discuss the assessment process and schedule.',
                ])

                <div class="grid grid-cols-2 gap-3 mt-4">
                    <div>
                        <label class="apis-form-label">Preferred Date *</label>
                        <input type="date" wire:model.live="form.mtgDate" min="{{ now()->addDay()->format('Y-m-d') }}" class="apis-form-control @error('form.mtgDate') apis-error @enderror">
                        @error('form.mtgDate') <p class="apis-error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="apis-form-label">Preferred Time *</label>
                        <input type="time" wire:model.live="form.mtgTime" class="apis-form-control @error('form.mtgTime') apis-error @enderror">
                        @error('form.mtgTime') <p class="apis-error-text">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 pb-10">
                    <button type="submit"
                            class="text-[13px] font-medium px-6 py-[9px] rounded-[8px] transition-colors"
                            style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd)">
                        Submit Assessment Meeting Request
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
