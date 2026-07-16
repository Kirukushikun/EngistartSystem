<div class="p-6 overflow-y-auto h-full">
    <div class="w-full">
        @include('partials.apis.filter-toolbar', [
            'gridClass' => 'grid-cols-1 md:grid-cols-[minmax(0,1.5fr)_180px]',
            'fields' => [
                [
                    'label' => 'Search',
                    'type' => 'text',
                    'placeholder' => 'Search by request ID, title, or farm...',
                    'class' => 'apis-toolbar-control',
                    'attributes' => ['wire:model.live.debounce.300ms' => 'search'],
                ],
                [
                    'label' => 'Sort',
                    'type' => 'select',
                    'class' => 'apis-toolbar-control',
                    'attributes' => ['wire:model.live' => 'sortBy'],
                    'options' => [
                        ['value' => 'latest', 'label' => 'Latest submitted'],
                        ['value' => 'needed_asc', 'label' => 'Date needed: earliest'],
                        ['value' => 'needed_desc', 'label' => 'Date needed: latest'],
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

        @forelse ($this->paginatedItems as $request)
            <div class="apis-card" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="w-full text-left p-[14px_18px] flex justify-between items-start gap-3" style="cursor: pointer;">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-[7px] mb-[5px] flex-wrap">
                            <span class="font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['id'] }}</span>
                            @include('partials.apis.request-status-badge', ['status' => $request['status'], 'label' => $request['statusLabel']])
                        </div>
                        <p class="text-[14px] font-medium m-0 mb-[3px] overflow-hidden text-ellipsis whitespace-nowrap text-apis-text">{{ $request['title'] }}</p>
                        <p class="text-[11px] text-apis-text2 m-0">{{ $request['farm'] }} · Start {{ $request['startDate'] }} · Completion {{ $request['completionDate'] }} · By {{ $request['by'] }}</p>
                    </div>
                    <span class="text-[10px] text-apis-text3 flex-shrink-0 mt-[3px]"><span x-show="!open">▼</span><span x-show="open">▲</span></span>
                </button>
                <div x-cloak x-show="open" class="border-t p-[16px_18px]" style="border-color: var(--border)">
                    <div class="mb-[14px] space-y-[8px] text-[12px] text-apis-text2">
                        <div><span class="mr-1">Type:</span><span class="text-apis-text">{{ $request['type'] }}</span></div>
                        <div><span class="mr-1">Project Start Date:</span><span class="text-apis-text">{{ $request['startDate'] }}</span></div>
                        <div><span class="mr-1">Project Completion Date:</span><span class="text-apis-text">{{ $request['completionDate'] }}</span></div>
                    </div>
                    @include('partials.apis.remarks-section', [
                        'history' => $request['remarkHistory'],
                        'showInput' => $request['isPendingHere'],
                        'textareaModel' => 'remarks.' . $request['id'],
                        'textareaPlaceholder' => 'Add initialization notes here...',
                    ])
                    <div class="flex gap-2 flex-wrap">
                        @if ($request['isPendingHere'])
                            <button type="button" wire:click="confirmInitialize(@js($request['id']))" class="apis-card-button font-medium" style="background: var(--green-bg); color: var(--green); border: 0.5px solid var(--green-bd)">Mark Initialized</button>
                        @else
                            <p class="text-[11px] text-apis-text2 m-0">View only. No further action is available on this request.</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-[80px] text-[13px] text-apis-text2">No requests assigned to you yet.</div>
        @endforelse

        @include('partials.apis.simple-pagination', [
            'summary' => 'Showing ' . $this->showingFrom . '-' . $this->showingTo . ' of ' . $this->filteredItems->count() . ' request' . ($this->filteredItems->count() !== 1 ? 's' : ''),
            'page' => $page,
            'totalPages' => $this->totalPages,
        ])
    </div>
</div>
