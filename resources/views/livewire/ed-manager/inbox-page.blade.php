<div class="p-6 overflow-y-auto h-full">
    <div class="w-full">
        @include('partials.apis.filter-toolbar', [
            'gridClass' => 'grid-cols-1 md:grid-cols-[minmax(0,1.5fr)_180px_180px]',
            'fields' => [
                [
                    'label' => 'Search',
                    'type' => 'text',
                    'placeholder' => 'Search by request ID, title, farm, or requester...',
                    'class' => 'apis-toolbar-control',
                    'attributes' => ['wire:model.live.debounce.300ms' => 'search'],
                ],
                [
                    'label' => 'Type',
                    'type' => 'select',
                    'class' => 'apis-toolbar-control',
                    'attributes' => ['wire:model.live' => 'typeFilter'],
                    'options' => array_merge(
                        [['value' => 'all', 'label' => 'All types']],
                        array_map(fn ($type) => ['value' => $type, 'label' => $type], $this->typeOptions)
                    ),
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

        @forelse ($this->paginatedInboxItems as $request)
            <div class="apis-card" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="w-full text-left p-[14px_18px] flex justify-between items-start gap-3" style="cursor: pointer;">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-[7px] mb-[5px] flex-wrap">
                            <span class="font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['id'] }}</span>
                            @include('partials.apis.request-status-badge', ['status' => $request['status'], 'label' => $request['statusLabel']])
                            @if ($request['isLate'])
                                <span class="text-[10px] px-[6px] py-[1px] rounded-[3px] font-medium"
                                      style="background: var(--amber-bg); color: var(--amber)">
                                    LATE
                                </span>
                            @endif
                        </div>
                        <p class="text-[14px] font-medium m-0 mb-[3px] overflow-hidden text-ellipsis whitespace-nowrap text-apis-text">{{ $request['title'] }}</p>
                        <p class="text-[11px] text-apis-text2 m-0">{{ $request['farm'] }} · Needed {{ $request['needed'] }} · {{ $request['days'] }}d ahead · By {{ $request['by'] }}</p>
                    </div>
                    <span class="text-[10px] text-apis-text3 flex-shrink-0 mt-[3px]"><span x-show="!open">▼</span><span x-show="open">▲</span></span>
                </button>
                <div x-cloak x-show="open" class="border-t p-[16px_18px]" style="border-color: var(--border)">
                    <div class="mb-[14px] space-y-[8px] text-[12px] text-apis-text2">
                        <div><span class="mr-1">Type:</span><span class="text-apis-text">{{ $request['type'] }}</span></div>
                        <div><span class="mr-1">Purpose:</span><span class="text-apis-text">{{ $request['purpose'] }}</span></div>
                        @if ($request['cap'])<div><span class="mr-1">Capacity:</span><span class="text-apis-text">{{ $request['cap'] }}</span></div>@endif
                        @if ($request['mtgDate'])<div><span class="mr-1">Meeting:</span><span class="text-apis-text">{{ $request['mtgDate'] }} at {{ $request['mtgTime'] }}</span></div>@endif
                    </div>
                    <p class="text-[12px] leading-[1.7] text-apis-text mb-[14px] border-l-2 pl-3" style="border-color: var(--border)">{{ $request['desc'] }}</p>
                    <div class="mb-[14px]">
                        <p class="text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Approval Chain</p>
                        <div class="flex flex-col gap-[7px]">
                            @foreach ($request['chain'] as $step)
                                @php
                                    $stepStyle = match ($step['st']) {
                                        'done' => ['bg' => 'var(--green-bg)', 'color' => 'var(--green)', 'symbol' => '✓'],
                                        'pending' => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)', 'symbol' => '●'],
                                        'rejected' => ['bg' => 'var(--red-bg)', 'color' => 'var(--red)', 'symbol' => '✕'],
                                        default => ['bg' => 'var(--gray-bg)', 'color' => 'var(--text3)', 'symbol' => '○'],
                                    };
                                @endphp
                                <div class="flex items-start gap-[9px]">
                                    <span class="apis-step-dot" style="background: {{ $stepStyle['bg'] }}; color: {{ $stepStyle['color'] }};">{{ $stepStyle['symbol'] }}</span>
                                    <div class="flex-1 pt-[1px]">
                                        <div class="flex justify-between items-baseline gap-2">
                                            <span class="text-[12px] {{ $step['st'] === 'waiting' ? 'text-apis-text3' : 'text-apis-text' }} {{ $step['st'] === 'pending' ? 'font-medium' : 'font-normal' }}">{{ $step['role'] }} — {{ $step['action'] }}</span>
                                            @if ($step['date'])
                                                <span class="text-[11px] text-apis-text3 flex-shrink-0">{{ $step['date'] }}</span>
                                            @endif
                                        </div>
                                        @if (($step['st'] === 'done' || $step['st'] === 'rejected') && $step['user'])
                                            <span class="text-[11px] text-apis-text2">{{ $step['user'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @include('partials.apis.remarks-section', [
                        'history' => $request['remarkHistory'],
                        'showInput' => $request['isPendingHere'],
                        'textareaModel' => 'remarks.' . $request['id'],
                        'textareaPlaceholder' => 'Add acceptance or return remarks here...',
                    ])
                    <div class="flex gap-2 flex-wrap">
                        @if ($request['isPendingHere'])
                            <button type="button" wire:click="confirmAccept(@js($request['id']))" class="apis-card-button font-medium" style="background: var(--green-bg); color: var(--green); border: 0.5px solid var(--green-bd)">Accept</button>
                            <button type="button" wire:click="confirmReturn(@js($request['id']))" class="apis-card-button font-medium" style="background: var(--red-bg); color: var(--red); border: 0.5px solid var(--red-bd)">Return</button>
                        @else
                            <p class="text-[11px] text-apis-text2 m-0">View only. No further action is available on this request from this stage.</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-[80px] text-[13px] text-apis-text2">No requests match the current filters.</div>
        @endforelse

        @include('partials.apis.simple-pagination', [
            'summary' => 'Showing ' . $this->showingFrom . '-' . $this->showingTo . ' of ' . $this->filteredInboxItems->count() . ' request' . ($this->filteredInboxItems->count() !== 1 ? 's' : ''),
            'page' => $page,
            'totalPages' => $this->totalPages,
        ])
    </div>
</div>
