@extends('layouts.app')

@section('title', 'Change Requests | EngiStart')
@section('header', 'Change Requests')
@section('subheader', 'Review settings change requests before they are forwarded for implementation.')

@section('content')
<div class="p-6 overflow-y-auto h-full">
    <div class="w-full">
        @if ($actionMessage)
            @include('partials.apis.alert', ['type' => $actionTone, 'message' => $actionMessage])
        @endif

        @if ($this->filteredChangeRequests->isNotEmpty())
            @include('partials.apis.alert', [
                'type' => 'warn',
                'message' => $this->filteredChangeRequests->count() . ' settings change request' . ($this->filteredChangeRequests->count() !== 1 ? 's' : '') . ' pending your approval.',
            ])
        @endif

        @include('partials.apis.filter-toolbar', [
            'gridClass' => 'grid-cols-1 md:grid-cols-[minmax(0,1.5fr)_180px_200px]',
            'fields' => [
                [
                    'label' => 'Search',
                    'type' => 'text',
                    'placeholder' => 'Search by request ID, setting, or requester...',
                    'class' => 'apis-toolbar-control',
                    'attributes' => ['wire:model.live.debounce.300ms' => 'search'],
                ],
                [
                    'label' => 'Status',
                    'type' => 'select',
                    'class' => 'apis-toolbar-control',
                    'attributes' => ['wire:model.live' => 'statusFilter'],
                    'options' => [
                        ['value' => 'pending_vp', 'label' => 'Pending VP Approval'],
                        ['value' => 'all', 'label' => 'All statuses'],
                    ],
                ],
                [
                    'label' => 'Sort',
                    'type' => 'select',
                    'class' => 'apis-toolbar-control',
                    'attributes' => ['wire:model.live' => 'sortBy'],
                    'options' => [
                        ['value' => 'latest', 'label' => 'Latest submitted'],
                        ['value' => 'setting_asc', 'label' => 'Setting A-Z'],
                        ['value' => 'setting_desc', 'label' => 'Setting Z-A'],
                    ],
                ],
            ],
            'trailingFields' => [
                [
                    'label' => 'Per page',
                    'type' => 'select',
                    'class' => 'apis-toolbar-control w-[92px]',
                    'attributes' => ['wire:model.live' => 'perPage'],
                    'options' => [
                        ['value' => '5', 'label' => '5'],
                        ['value' => '10', 'label' => '10'],
                        ['value' => '15', 'label' => '15'],
                    ],
                ],
            ],
        ])

        @forelse ($this->paginatedChangeRequests as $request)
            <div class="apis-cr-card" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="w-full text-left p-[14px_18px] flex justify-between items-start gap-3" style="cursor: pointer;">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-[7px] mb-[5px] flex-wrap">
                            <span class="font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['id'] }}</span>
                            <span class="text-[11px] px-2 py-0.5 rounded font-medium" style="background: var(--amber-bg); color: var(--amber)">Pending VP Approval</span>
                        </div>
                        <p class="text-[14px] font-medium m-0 mb-[3px] text-apis-text">Change: {{ $request['setting'] }}</p>
                        <p class="text-[11px] text-apis-text2 m-0">Requested by {{ $request['requestedBy'] }} ({{ $request['requestedRole'] }}) · {{ $request['requestedAt'] }}</p>
                    </div>
                    <span class="text-[10px] text-apis-text3 flex-shrink-0 mt-[3px]">
                        <span x-show="!open">▼</span>
                        <span x-show="open">▲</span>
                    </span>
                </button>

                <div x-cloak x-show="open" class="border-t p-[16px_18px]" style="border-color: var(--border)">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-[8px_24px] text-[12px] mb-[14px]">
                        <div><span class="text-apis-text2 mr-1">Current value:</span><span class="font-medium" style="color: var(--red)">{{ $request['oldVal'] }}</span></div>
                        <div><span class="text-apis-text2 mr-1">Proposed value:</span><span class="font-medium" style="color: var(--green)">{{ $request['newVal'] }}</span></div>
                    </div>

                    <div class="rounded-[8px] p-[12px_14px] mb-[14px] text-[12px] leading-[1.6]" style="background: var(--bg2)">
                        <p class="text-[10px] text-apis-text2 mb-1 font-medium uppercase tracking-[0.07em]">Justification</p>
                        <p class="m-0 text-apis-text">{{ $request['reason'] }}</p>
                    </div>

                    <div class="mb-[14px]">
                        <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Remarks</label>
                        <textarea wire:model.live="remarks.{{ $request['id'] }}" class="apis-remarks-control" placeholder="Add remarks..."></textarea>
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        <button type="button" wire:click="approve(@js($request['id']))" class="apis-card-button font-medium" style="background: var(--green-bg); color: var(--green); border: 0.5px solid var(--green-bd)">Approve</button>
                        <button type="button" wire:click="reject(@js($request['id']))" class="apis-card-button font-medium" style="background: var(--red-bg); color: var(--red); border: 0.5px solid var(--red-bd)">Reject</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-[80px] text-[13px] text-apis-text2">No change requests match the current filters.</div>
        @endforelse

        @include('partials.apis.simple-pagination', [
            'summary' => 'Showing ' . $this->showingFrom . '-' . $this->showingTo . ' of ' . $this->filteredChangeRequests->count() . ' request' . ($this->filteredChangeRequests->count() !== 1 ? 's' : ''),
            'page' => $page,
            'totalPages' => $this->totalPages,
        ])
    </div>
</div>
@endsection
