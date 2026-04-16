@extends('layouts.app')

@section('title', 'New Request | EngiStart')
@section('header', 'New Request')
@section('subheader', 'Create and submit a project initialization request.')

@section('content')
<div class="p-6 overflow-y-auto h-full">
    <div class="max-w-[640px]">

        @if ($submitted)
            <div class="flex flex-col items-center text-center gap-3 max-w-[480px] mx-auto mt-10 px-2">
                <div class="w-[52px] h-[52px] rounded-full flex items-center justify-center text-[22px]"
                     style="background: var(--green-bg); color: var(--green)">✓</div>

                <p class="text-[16px] font-medium text-apis-text">Request submitted</p>

                <p class="text-[12px] text-apis-text2 leading-[1.6]">
                    {{ $isLate ? 'Routed to DH Gen Services for late-filing review.' : 'Now in the standard approval workflow.' }}
                </p>

                <div class="w-full rounded-[8px] p-[12px_20px] text-[12px] text-left" style="background: var(--bg2)">
                    <div class="flex justify-between mb-1">
                        <span class="text-apis-text2">Request ID</span>
                        <span class="font-mono font-medium text-apis-text">{{ $submittedId }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-apis-text2">Routing</span>
                        <span class="font-medium text-[11px] px-2 py-0.5 rounded"
                              style="background: {{ $isLate ? 'var(--amber-bg)' : 'var(--blue-bg)' }}; color: {{ $isLate ? 'var(--amber)' : 'var(--blue)' }};">
                            {{ $isLate ? 'Late Filing – DH Gen Services' : 'Standard Workflow' }}
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
            <form wire:submit="submit" class="contents">

                {{-- Memo header --}}
                <div class="grid grid-cols-2 gap-x-5 gap-y-1 text-[12px] rounded-[8px] p-[11px_16px] mb-4"
                     style="background: var(--bg2); border: 0.5px solid var(--border)">
                    <div><span class="text-apis-text2">TO: </span><span class="font-medium">Div. Head Santos</span></div>
                    <div><span class="text-apis-text2">DATE: </span><span class="font-medium">{{ now()->format('F j, Y') }}</span></div>
                    <div><span class="text-apis-text2">FROM: </span><span class="font-medium">Jose Santos</span></div>
                    <div><span class="text-apis-text2">FARM: </span><span class="font-medium">Farm A – Bamban, Tarlac</span></div>
                </div>

                {{-- Info alert --}}
                @include('partials.apis.alert', [
                    'type' => 'info',
                    'message' => 'Requests must be submitted at least 45 days before the project start date. Late submissions require a Justification Letter and will be routed directly to DH Gen Services.',
                ])

                {{-- ── PROJECT OVERVIEW ──────────────────────────────── --}}
                @include('partials.apis.section-divider', ['label' => 'Project Overview'])

                <div class="grid grid-cols-2 gap-3 mt-3">
                    {{-- Project Title --}}
                    <div>
                        <label class="apis-form-label">Project Title *</label>
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
                            <option>Building</option>
                            <option>Infrastructure</option>
                            <option>Equipment</option>
                            <option>Utility</option>
                            <option>Others</option>
                        </select>
                        @error('form.type') <p class="apis-error-text">{{ $message }}</p> @enderror
                    </div>

                    {{-- Purpose --}}
                    <div class="col-span-2">
                        <label class="apis-form-label">Purpose</label>
                        <input type="text"
                               wire:model.live="form.purpose"
                               placeholder="Describe the purpose..."
                               class="apis-form-control">
                    </div>
                </div>

                {{-- ── DATE NEEDED ───────────────────────────────────── --}}
                <div class="mt-4">
                    <label class="apis-form-label">
                        Date Needed *
                        @if (!is_null($daysAway))
                            <span class="font-medium text-[11px]"
                                  style="color: {{ $isPast ? 'var(--red)' : ($isLate ? 'var(--red)' : 'var(--green)') }};">
                                {{ $isPast ? '(Date is in the past)' : ($isLate ? "({$daysAway} days away — below 45-day minimum)" : "({$daysAway} days ahead — within required window)") }}
                            </span>
                        @endif
                    </label>
                    <input type="date"
                           wire:model.live="form.needed"
                           min="{{ now()->addDay()->format('Y-m-d') }}"
                           class="apis-form-control @error('form.needed') apis-error @enderror">
                    @error('form.needed') <p class="apis-error-text">{{ $message }}</p> @enderror
                </div>

                {{-- ── LATE FILING BLOCK ─────────────────────────────── --}}
                @if ($isLate && !$isPast)
                    <div class="mt-4 rounded-[8px] p-[16px_18px]"
                         style="background: var(--red-bg); border: 0.5px solid var(--red-bd)">
                        <p class="text-[13px] font-medium mb-1.5" style="color: var(--red)">Submission deadline exceeded</p>
                        <p class="text-[12px] leading-[1.65] mb-3" style="color: var(--red)">
                            Only {{ $daysAway }} day{{ $daysAway === 1 ? '' : 's' }} before start date. A Justification Letter is required and this will route to DH Gen Services.
                        </p>
                        <div class="flex items-start gap-2 mb-3">
                            <input type="checkbox" id="ack" wire:model.live="proceed" class="mt-0.5 cursor-pointer" style="width:auto">
                            <label for="ack" class="text-[12px] cursor-pointer m-0" style="color: var(--red)">
                                I acknowledge this is a late filing and will provide a Justification Letter
                            </label>
                        </div>
                        @error('proceed') <p class="apis-error-text" style="color: var(--red);">{{ $message }}</p> @enderror

                        @if ($proceed)
                            <div class="mt-3">
                                <label class="text-[12px] block mb-1" style="color: var(--red)">Attach Justification Letter (JL) *</label>
                                <input type="file"
                                       wire:model="justificationLetter"
                                       accept=".pdf,.doc,.docx"
                                       class="apis-form-control text-[12px] @error('justificationLetter') apis-error @enderror"
                                       style="color: var(--text)">
                                @error('justificationLetter') <p class="apis-error-text">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ── PROJECT REQUIREMENT ───────────────────────────── --}}
                @include('partials.apis.section-divider', ['label' => 'Project Requirement'])

                <div class="mt-3">
                    <label class="apis-form-label">Detailed Description *</label>
                    <textarea rows="4"
                              wire:model.live="form.desc"
                              placeholder="Describe the project scope and requirements..."
                              class="apis-form-control @error('form.desc') apis-error @enderror"></textarea>
                    @error('form.desc') <p class="apis-error-text">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="apis-form-label">Chick-in Date (if applicable)</label>
                        <input type="date"
                               wire:model.live="form.chickin"
                               class="apis-form-control">
                    </div>
                    <div>
                        <label class="apis-form-label">Capacity</label>
                        <input type="text"
                               wire:model.live="form.cap"
                               placeholder="e.g. 25,000 heads"
                               class="apis-form-control">
                    </div>
                </div>

                {{-- ── ASSESSMENT MEETING ────────────────────────────── --}}
                @include('partials.apis.section-divider', ['label' => 'Assessment Meeting Request'])

                <p class="text-[12px] text-apis-text2 mb-3 leading-[1.6]">
                    We kindly request your availability for a meeting to discuss the assessment process and schedule.
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="apis-form-label">Preferred Date</label>
                        <input type="date"
                               wire:model.live="form.mtgDate"
                               class="apis-form-control">
                    </div>
                    <div>
                        <label class="apis-form-label">Preferred Time</label>
                        <input type="time"
                               wire:model.live="form.mtgTime"
                               class="apis-form-control">
                    </div>
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
                            style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd)">
                        Submit Request
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
@endsection