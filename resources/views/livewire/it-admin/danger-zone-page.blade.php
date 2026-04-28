<div class="p-6 overflow-y-auto h-full">
    <div class="max-w-[820px]">
        <div class="rounded-[12px] p-[16px_18px] mb-5" style="border: 0.5px solid rgba(239, 68, 68, 0.35); background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.04) 100%);">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-[10px]" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.25); color: var(--red);">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <p class="text-[13px] font-medium mb-1" style="color: #fca5a5;">Destructive Actions Ahead</p>
                    <p class="text-[12px] leading-[1.7] m-0" style="color: #f87171;">These tools are intended for exceptional maintenance only. Preview counts are live, but execution remains intentionally guarded until backend deletion rules are explicitly approved.</p>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-[14px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg);">
                <div class="px-[18px] py-[14px] flex items-start gap-3" style="border-bottom: 0.5px solid var(--border); background: var(--bg2);">
                    <div class="mt-1 h-2 w-2 rounded-full" style="background: var(--red); box-shadow: 0 0 8px rgba(239, 68, 68, 0.45);"></div>
                    <div>
                        <p class="text-[13px] font-medium m-0 text-apis-text">Wipe Project Requests</p>
                        <p class="text-[11px] m-0 mt-1 text-apis-text2">Delete project request records across the system using all, date range, or year filters.</p>
                    </div>
                </div>
                <div class="p-[18px] space-y-4">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.08em] mb-2 text-apis-text2">Select wipe mode</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            @foreach ([['all', 'Wipe All', 'Delete all project requests'], ['date_range', 'Date Range', 'Delete within a date range'], ['year', 'By Year', 'Delete all requests for a year']] as [$value, $label, $description])
                                <label class="rounded-[10px] p-[12px_14px] cursor-pointer" style="border: 1px solid {{ $wipeMode === $value ? 'rgba(239, 68, 68, 0.45)' : 'var(--border)' }}; background: {{ $wipeMode === $value ? 'rgba(239, 68, 68, 0.08)' : 'var(--bg2)' }};">
                                    <input type="radio" wire:model.live="wipeMode" value="{{ $value }}" class="hidden">
                                    <p class="text-[12px] font-medium m-0 text-apis-text">{{ $label }}</p>
                                    <p class="text-[11px] m-0 mt-1 text-apis-text2">{{ $description }}</p>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if ($wipeMode === 'date_range')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="apis-form-label">From Date</label>
                                <input type="date" wire:model.live="wipeFrom" class="apis-toolbar-control w-full">
                            </div>
                            <div>
                                <label class="apis-form-label">To Date</label>
                                <input type="date" wire:model.live="wipeTo" class="apis-toolbar-control w-full">
                            </div>
                        </div>
                    @elseif ($wipeMode === 'year')
                        <div class="max-w-[260px]">
                            <label class="apis-form-label">Select Year</label>
                            <select wire:model.live="wipeYear" class="apis-toolbar-control w-full">
                                <option value="">-- Select Year --</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="previewCount('wipe')" class="apis-card-button" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text2);">Preview Count</button>
                        @if (!is_null($wipeCount))
                            <span class="text-[12px] px-3 py-1 rounded-full font-medium" style="color: #fca5a5; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.25);">{{ number_format($wipeCount) }} requests will be deleted</span>
                        @endif
                    </div>

                    <div class="pt-4" style="border-top: 0.5px solid var(--border);">
                        <button type="button" wire:click="openConfirm('wipe')" class="apis-card-button font-medium" style="background: #dc2626; color: #fff; border: none;">Wipe Requests</button>
                    </div>
                </div>
            </div>

            <div class="rounded-[14px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg);">
                <div class="px-[18px] py-[14px] flex items-start gap-3" style="border-bottom: 0.5px solid var(--border); background: var(--bg2);">
                    <div class="mt-1 h-2 w-2 rounded-full" style="background: #f97316; box-shadow: 0 0 8px rgba(249, 115, 22, 0.45);"></div>
                    <div>
                        <p class="text-[13px] font-medium m-0 text-apis-text">Purge Attachment Files</p>
                        <p class="text-[11px] m-0 mt-1 text-apis-text2">Preview requests with attachments by quarter or custom date range before purging stored files.</p>
                    </div>
                </div>
                <div class="p-[18px] space-y-4">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.08em] mb-2 text-apis-text2">Select purge mode</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach ([['quarter', 'By Quarter', 'Select a year and quarter (Q1–Q4)'], ['custom', 'Custom Range', 'Specify a custom date range']] as [$value, $label, $description])
                                <label class="rounded-[10px] p-[12px_14px] cursor-pointer" style="border: 1px solid {{ $photoMode === $value ? 'rgba(249, 115, 22, 0.45)' : 'var(--border)' }}; background: {{ $photoMode === $value ? 'rgba(249, 115, 22, 0.08)' : 'var(--bg2)' }};">
                                    <input type="radio" wire:model.live="photoMode" value="{{ $value }}" class="hidden">
                                    <p class="text-[12px] font-medium m-0 text-apis-text">{{ $label }}</p>
                                    <p class="text-[11px] m-0 mt-1 text-apis-text2">{{ $description }}</p>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if ($photoMode === 'quarter')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="apis-form-label">Select Year</label>
                                <select wire:model.live="photoYear" class="apis-toolbar-control w-full">
                                    <option value="">-- Select Year --</option>
                                    @foreach ($this->availableYears as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="apis-form-label">Select Quarter</label>
                                <select wire:model.live="photoQuarter" class="apis-toolbar-control w-full" @disabled($this->quarterDisabled)>
                                    <option value="">-- Select Quarter --</option>
                                    <option value="Q1">Q1 — Jan–Mar</option>
                                    <option value="Q2">Q2 — Apr–Jun</option>
                                    <option value="Q3">Q3 — Jul–Sep</option>
                                    <option value="Q4">Q4 — Oct–Dec</option>
                                </select>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="apis-form-label">From Date</label>
                                <input type="date" wire:model.live="photoFrom" class="apis-toolbar-control w-full">
                            </div>
                            <div>
                                <label class="apis-form-label">To Date</label>
                                <input type="date" wire:model.live="photoTo" class="apis-toolbar-control w-full">
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="previewCount('photo')" class="apis-card-button" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text2);">Preview Count</button>
                        @if (!is_null($photoCount))
                            <span class="text-[12px] px-3 py-1 rounded-full font-medium" style="color: #fdba74; background: rgba(249, 115, 22, 0.12); border: 1px solid rgba(249, 115, 22, 0.25);">{{ number_format($photoCount) }} requests have attachments to purge</span>
                        @endif
                    </div>

                    <div class="pt-4" style="border-top: 0.5px solid var(--border);">
                        <button type="button" wire:click="openConfirm('photo')" class="apis-card-button font-medium" style="background: #ea6f0e; color: #fff; border: none;">Purge Attachment Files</button>
                    </div>
                </div>
            </div>

            <div class="rounded-[14px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg);">
                <div class="px-[18px] py-[14px] flex items-start gap-3" style="border-bottom: 0.5px solid var(--border); background: var(--bg2);">
                    <div class="mt-1 h-2 w-2 rounded-full" style="background: var(--red); box-shadow: 0 0 8px rgba(239, 68, 68, 0.45);"></div>
                    <div>
                        <p class="text-[13px] font-medium m-0 text-apis-text">Purge Activity Logs</p>
                        <p class="text-[11px] m-0 mt-1 text-apis-text2">Preview audit trail entries by all, date range, or year before any deletion workflow is enabled.</p>
                    </div>
                </div>
                <div class="p-[18px] space-y-4">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.08em] mb-2 text-apis-text2">Select purge mode</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            @foreach ([['all', 'Purge All', 'Delete all activity logs'], ['date_range', 'Date Range', 'Delete within a date range'], ['year', 'By Year', 'Delete all logs for a year']] as [$value, $label, $description])
                                <label class="rounded-[10px] p-[12px_14px] cursor-pointer" style="border: 1px solid {{ $logMode === $value ? 'rgba(239, 68, 68, 0.45)' : 'var(--border)' }}; background: {{ $logMode === $value ? 'rgba(239, 68, 68, 0.08)' : 'var(--bg2)' }};">
                                    <input type="radio" wire:model.live="logMode" value="{{ $value }}" class="hidden">
                                    <p class="text-[12px] font-medium m-0 text-apis-text">{{ $label }}</p>
                                    <p class="text-[11px] m-0 mt-1 text-apis-text2">{{ $description }}</p>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if ($logMode === 'date_range')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="apis-form-label">From Date</label>
                                <input type="date" wire:model.live="logFrom" class="apis-toolbar-control w-full">
                            </div>
                            <div>
                                <label class="apis-form-label">To Date</label>
                                <input type="date" wire:model.live="logTo" class="apis-toolbar-control w-full">
                            </div>
                        </div>
                    @elseif ($logMode === 'year')
                        <div class="max-w-[260px]">
                            <label class="apis-form-label">Select Year</label>
                            <select wire:model.live="logYear" class="apis-toolbar-control w-full">
                                <option value="">-- Select Year --</option>
                                @foreach ($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="previewCount('log')" class="apis-card-button" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text2);">Preview Count</button>
                        @if (!is_null($logCount))
                            <span class="text-[12px] px-3 py-1 rounded-full font-medium" style="color: #fca5a5; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.25);">{{ number_format($logCount) }} log entries would be deleted</span>
                        @endif
                    </div>

                    <div class="pt-4" style="border-top: 0.5px solid var(--border);">
                        <button type="button" wire:click="openConfirm('log')" class="apis-card-button font-medium" style="background: #dc2626; color: #fff; border: none;">Purge Activity Logs</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($confirmingGroup)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0, 0, 0, 0.72); backdrop-filter: blur(4px);">
            <div class="w-full max-w-[440px] rounded-[16px] p-7" style="background: var(--bg); border: 0.5px solid var(--border);">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full" style="background: {{ $confirmingGroup === 'photo' ? 'rgba(249, 115, 22, 0.12)' : 'rgba(239, 68, 68, 0.12)' }}; border: 1px solid {{ $confirmingGroup === 'photo' ? 'rgba(249, 115, 22, 0.25)' : 'rgba(239, 68, 68, 0.25)' }}; color: {{ $confirmingGroup === 'photo' ? '#fb923c' : '#f87171' }};">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <p class="text-[16px] font-medium text-center mb-2 text-apis-text">Confirm Destructive Action</p>
                <p class="text-[12px] text-center leading-[1.7] mb-5 text-apis-text2">Type <span class="font-mono font-medium" style="color: {{ $confirmingGroup === 'photo' ? '#fb923c' : '#f87171' }};">{{ number_format($this->confirmCount) }}</span> to confirm this action preview. Execution is still blocked until backend deletion rules are finalized.</p>
                <div>
                    <label class="apis-form-label">Confirmation Value</label>
                    <input type="text" wire:model.live="confirmInput" class="apis-toolbar-control w-full font-mono" placeholder="Enter {{ number_format($this->confirmCount) }}">
                </div>
                <div class="mt-5 flex justify-center gap-3">
                    <button type="button" wire:click="closeConfirm" class="apis-card-button" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text2);">Cancel</button>
                    <button type="button" wire:click="queueAction" class="apis-card-button font-medium" @disabled(!$this->canConfirm) style="background: {{ $confirmingGroup === 'photo' ? '#ea6f0e' : '#dc2626' }}; color: #fff; border: none; opacity: {{ $this->canConfirm ? '1' : '0.45' }};">Continue</button>
                </div>
            </div>
        </div>
    @endif
</div>
