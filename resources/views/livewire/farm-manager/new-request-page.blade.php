<div class="p-6 overflow-y-auto h-full">
    <div class="max-w-[640px] @if($submitted) mx-auto min-h-full flex items-center justify-center @endif">

        @if ($submitted)
            <div class="flex w-full flex-col items-center justify-center text-center gap-3 max-w-[480px] px-2">
                <div class="w-[52px] h-[52px] rounded-full flex items-center justify-center text-[22px]"
                     style="background: var(--green-bg); color: var(--green)">✓</div>

                <p class="text-[16px] font-medium text-apis-text">Request submitted</p>

                <p class="text-[12px] text-apis-text2 leading-[1.6]">
                    {{ $timelineAcceptable === 'no' ? 'Justification Letter routed to Division Head and VP Gen Services for review.' : 'Please complete the Assessment Meeting Request from My Requests to continue.' }}
                </p>

                <div class="w-full rounded-[8px] p-[12px_20px] text-[12px] text-left" style="background: var(--bg2)">
                    <div class="flex justify-between mb-1">
                        <span class="text-apis-text2">Request ID</span>
                        <span class="font-mono font-medium text-apis-text">{{ $submittedId }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-apis-text2">Routing</span>
                        <span class="font-medium text-[11px] px-2 py-0.5 rounded"
                              style="background: {{ $timelineAcceptable === 'no' ? 'var(--amber-bg)' : 'var(--blue-bg)' }}; color: {{ $timelineAcceptable === 'no' ? 'var(--amber)' : 'var(--blue)' }};">
                            {{ $timelineAcceptable === 'no' ? 'Justification Letter Review' : 'Assessment Meeting Request' }}
                        </span>
                    </div>
                </div>

                <button type="button"
                        wire:click="resetForm"
                        class="mt-1 text-xs text-apis-text2 px-4 py-2 rounded transition-colors hover:bg-apis-bg2"
                        style="border: 0.5px solid var(--border2)">
                    Submit another request
                </button>
            </div>
        @else
            <form wire:submit="openSubmissionReview" class="contents">

                @if ($isEditing)
                    <div class="rounded-[8px] p-[10px_14px] mb-[14px]"
                         style="background: var(--amber-bg); border: 0.5px solid var(--amber-bd)">
                        <p class="text-[12px] leading-[1.6] m-0 font-normal" style="color: var(--amber)">
                            You are editing an existing request. Changes are only allowed until the first reviewer action.
                        </p>
                    </div>
                @endif

                {{-- Memo header --}}
                <div class="grid grid-cols-2 gap-x-5 gap-y-1 text-[12px] rounded-[8px] p-[11px_16px] mb-4"
                     style="background: var(--bg2); border: 0.5px solid var(--border)">
                    <div><span class="text-apis-text2">TO: </span><span class="font-medium">Div. Head Santos</span></div>
                    <div><span class="text-apis-text2">DATE OF REQUEST: </span><span class="font-medium">{{ now()->format('F j, Y') }}</span></div>
                    <div><span class="text-apis-text2">FROM: </span><span class="font-medium">Jose Santos</span></div>
                    <div><span class="text-apis-text2">FARM: </span><span class="font-medium">Farm A – Bamban, Tarlac</span></div>
                </div>

                {{-- Info alert --}}
                @include('partials.apis.alert', [
                    'type' => 'info',
                    'message' => 'Please allow at least 30 days for small, 45 days for big projects when planning your submission.',
                ])

                {{-- ── PROJECT OVERVIEW ──────────────────────────────── --}}
                @include('partials.apis.section-divider', ['label' => 'Project Overview'])

                <div class="grid grid-cols-2 gap-3 mt-3">
                    {{-- Project Description based on CAPEX --}}
                    <div class="col-span-2">
                        <label class="apis-form-label">Project Description based on CAPEX *</label>
                        <input type="text"
                               wire:model.live="form.title"
                               placeholder="e.g. Poultry House Renovation"
                               class="apis-form-control @error('form.title') apis-error @enderror">
                        @error('form.title') <p class="apis-error-text">{{ $message }}</p> @enderror
                    </div>

                    {{-- Type --}}
                    <div>
                        <label class="apis-form-label">Type *</label>
                        <select wire:model.live="form.type" class="apis-form-control @error('form.type') apis-error @enderror">
                            <option value="">Select type...</option>
                            @foreach ($this->typeOptions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('form.type') <p class="apis-error-text">{{ $message }}</p> @enderror
                    </div>

                    {{-- Allotted Budget --}}
                    <div>
                        <label class="apis-form-label">Allotted Budget *</label>
                        <select wire:model.live="form.budgetCategory" class="apis-form-control @error('form.budgetCategory') apis-error @enderror">
                            <option value="">Select budget range...</option>
                            @foreach ($this->budgetCategoryOptions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('form.budgetCategory') <p class="apis-error-text">{{ $message }}</p> @enderror
                    </div>

                    @if ($form['type'] === 'others')
                        <div class="col-span-2">
                            <label class="apis-form-label">Please specify Type *</label>
                            <input type="text"
                                   wire:model.live="form.typeOther"
                                   placeholder="Specify the project type..."
                                   class="apis-form-control @error('form.typeOther') apis-error @enderror">
                            @error('form.typeOther') <p class="apis-error-text">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    {{-- Purpose --}}
                    <div class="col-span-2">
                        <label class="apis-form-label">Purpose</label>
                        <input type="text"
                               wire:model.live="form.purpose"
                               placeholder="Describe the purpose..."
                               class="apis-form-control">
                    </div>
                </div>

                {{-- ── AUTO-CALCULATED TIMELINE ──────────────────────── --}}
                <div class="mt-4">
                    <label class="apis-form-label">Date Needed *</label>
                    <input type="date"
                           wire:model.live="form.needed"
                           min="{{ now()->addDay()->format('Y-m-d') }}"
                           class="apis-form-control @error('form.needed') apis-error @enderror">
                    @error('form.needed') <p class="apis-error-text">{{ $message }}</p> @enderror
                </div>

                @if ($this->computedTimeline)
                    <div class="grid grid-cols-2 gap-3 mt-3">
                        <div>
                            <label class="apis-form-label">Project Start Date</label>
                            <input type="text" readonly value="{{ $this->computedTimeline['start_date']->format('F j, Y') }}" class="apis-form-control" style="opacity: 0.75; cursor: not-allowed;">
                        </div>
                        <div>
                            <label class="apis-form-label">Project Completion Date</label>
                            <input type="text" readonly value="{{ $this->computedTimeline['completion_date']->format('F j, Y') }}" class="apis-form-control" style="opacity: 0.75; cursor: not-allowed;">
                        </div>
                    </div>
                @endif

                {{-- ── TIMELINE ACCEPTABILITY ────────────────────────── --}}
                <div class="mt-4 rounded-[8px] p-[14px_16px]" style="background: var(--bg2); border: 0.5px solid var(--border)">
                    <label class="apis-form-label">Is the estimated timeline acceptable? *</label>
                    <div class="flex gap-4 mt-1">
                        <label class="flex items-center gap-1.5 text-[12px] cursor-pointer text-apis-text">
                            <input type="radio" wire:model.live="timelineAcceptable" value="yes" style="width:auto"> Yes
                        </label>
                        <label class="flex items-center gap-1.5 text-[12px] cursor-pointer text-apis-text">
                            <input type="radio" wire:model.live="timelineAcceptable" value="no" style="width:auto"> No
                        </label>
                    </div>
                    @error('timelineAcceptable') <p class="apis-error-text">{{ $message }}</p> @enderror

                    @if ($timelineAcceptable === 'no')
                        <div class="mt-4 pt-4 space-y-3" style="border-top: 0.5px solid var(--border)">
                            <p class="text-[12px] font-medium m-0" style="color: var(--red)">Justification Letter (JL)</p>

                            <div>
                                <label class="apis-form-label">Reason for PIF delay *</label>
                                <textarea rows="2" wire:model.live="jl.delayReason" class="apis-form-control @error('jl.delayReason') apis-error @enderror"></textarea>
                                @error('jl.delayReason') <p class="apis-error-text">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="apis-form-label">Estimated turnover date *</label>
                                <input type="date" wire:model.live="jl.estimatedTurnoverDate" class="apis-form-control @error('jl.estimatedTurnoverDate') apis-error @enderror">
                                @error('jl.estimatedTurnoverDate') <p class="apis-error-text">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="apis-form-label">Implication if not completed *</label>
                                <textarea rows="2" wire:model.live="jl.implicationIfNotCompleted" class="apis-form-control @error('jl.implicationIfNotCompleted') apis-error @enderror"></textarea>
                                @error('jl.implicationIfNotCompleted') <p class="apis-error-text">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="apis-form-label">Estimated financial opportunity loss *</label>
                                <input type="text" wire:model.live="jl.estimatedFinancialOpportunityLoss" placeholder="e.g. ₱150,000" class="apis-form-control @error('jl.estimatedFinancialOpportunityLoss') apis-error @enderror">
                                @error('jl.estimatedFinancialOpportunityLoss') <p class="apis-error-text">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif
                </div>

                @if ($errors->any())
                    <div class="mt-4 rounded-[8px] p-[10px_14px]"
                         style="background: var(--red-bg); border: 0.5px solid var(--red-bd)">
                        <p class="text-[12px] m-0" style="color: var(--red)">Please complete all required fields before submitting.</p>
                    </div>
                @endif

                {{-- ── ACTIONS ───────────────────────────────────────── --}}
                <div class="flex gap-2.5 mt-6 pb-10">
                    <button type="submit"
                            class="text-[13px] font-medium px-6 py-[9px] rounded-[8px] transition-colors"
                            style="background: {{ $timelineAcceptable === 'no' ? 'var(--red-bg)' : 'var(--blue-bg)' }}; color: {{ $timelineAcceptable === 'no' ? 'var(--red)' : 'var(--blue)' }}; border: 0.5px solid {{ $timelineAcceptable === 'no' ? 'var(--red-bd)' : 'var(--blue-bd)' }}">
                        {{ $timelineAcceptable === 'no' ? 'Submit JL' : ($isEditing ? 'Review and Save Changes' : 'Review Before Submit') }}
                    </button>
                    <button type="button"
                            wire:click="resetForm"
                            class="text-xs px-4 py-[6px] rounded-[8px] text-apis-text2 transition-colors hover:bg-apis-bg2"
                            style="border: 0.5px solid var(--border2)">
                        Clear
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
