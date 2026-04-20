<div class="p-6 overflow-y-auto h-full">
    <div class="w-full">
        @include('partials.apis.filter-toolbar', [
            'gridClass' => 'grid-cols-1 md:grid-cols-[minmax(0,1.5fr)_190px_190px]',
            'fields' => [
                [
                    'label' => 'Search',
                    'type' => 'text',
                    'placeholder' => 'Search by request ID, title, or status...',
                    'class' => 'apis-toolbar-control w-full',
                    'attributes' => ['wire:model.live.debounce.300ms' => 'search'],
                ],
                [
                    'label' => 'Status',
                    'type' => 'select',
                    'class' => 'apis-toolbar-control w-full',
                    'attributes' => ['wire:model.live' => 'statusFilter'],
                    'options' => array_merge(
                        [['value' => 'all', 'label' => 'All statuses']],
                        $this->statusOptions
                    ),
                ],
                [
                    'label' => 'Sort',
                    'type' => 'select',
                    'class' => 'apis-toolbar-control w-full',
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

        @if ($this->filteredRequests->isEmpty())
            <div class="text-center py-[60px] text-[13px] text-apis-text2">
                No requests match the current filters.
            </div>
        @else
            @foreach ($this->paginatedRequests as $request)
                @php
                    $statusLabel = match ($request['status']) {
                        'recommended' => 'DH Recommended',
                        'vp_approved' => 'VP Approved',
                        'late_pending' => 'Late – Pending',
                        'returned_to_requestor' => 'Returned to Requestor',
                        default => ucfirst(str_replace('_', ' ', $request['status'])),
                    };

                    $stepMap = [
                        'done' => ['bg' => 'var(--green-bg)', 'color' => 'var(--green)', 'dotBg' => 'var(--green-bg)', 'dotColor' => 'var(--green)', 'symbol' => '✓'],
                        'pending' => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)', 'dotBg' => 'var(--blue-bg)', 'dotColor' => 'var(--blue)', 'symbol' => '●'],
                        'rejected' => ['bg' => 'var(--red-bg)', 'color' => 'var(--red)', 'dotBg' => 'var(--red-bg)', 'dotColor' => 'var(--red)', 'symbol' => '✕'],
                        'waiting' => ['bg' => 'var(--gray-bg)', 'color' => 'var(--text3)', 'dotBg' => 'var(--gray-bg)', 'dotColor' => 'var(--text3)', 'symbol' => '○'],
                    ];
                @endphp

                <div class="rounded-[12px] bg-apis-bg p-[14px_18px] mb-2.5"
                     style="border: 0.5px solid var(--border)">
                    <div class="flex items-start justify-between gap-4 mb-1 flex-wrap">
                        <div class="flex gap-[7px] items-center flex-wrap">
                            <span class="font-mono text-[11px] text-apis-text2">{{ $request['id'] }}</span>
                            @include('partials.apis.request-status-badge', ['status' => $request['status'], 'label' => $statusLabel])
                            @if ($request['isLate'])
                                <span class="text-[10px] px-[6px] py-[1px] rounded-[3px] font-medium"
                                      style="background: var(--amber-bg); color: var(--amber)">
                                    LATE
                                </span>
                            @endif
                        </div>

                        @if ($request['isEditable'])
                            <div class="flex gap-2 flex-wrap justify-end">
                                <a href="{{ route('farm-manager.requests.new', ['edit' => $request['dbId']]) }}"
                                   class="text-[11px] font-medium px-3 py-1.5 rounded-[8px] no-underline"
                                   style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd)">
                                    Edit before review
                                </a>
                                <button type="button"
                                        wire:click="confirmWithdraw({{ $request['dbId'] }})"
                                        class="text-[11px] font-medium px-3 py-1.5 rounded-[8px]"
                                        style="background: var(--red-bg); color: var(--red); border: 0.5px solid var(--red-bd)">
                                    Withdraw
                                </button>
                            </div>
                        @endif
                    </div>

                    <p class="text-[13px] font-medium m-0 mb-[2px] text-apis-text">{{ $request['title'] }}</p>
                    <p class="text-[11px] text-apis-text2 m-0 mb-[10px]">
                        Needed: {{ $request['needed'] }} · Submitted: {{ $request['submitted'] }}
                    </p>

                    @if ($request['isWithdrawn'])
                        <p class="text-[11px] text-apis-text2 m-0 mb-[10px]">
                            This request was withdrawn before reviewer pickup.
                        </p>
                    @endif

                    <p class="text-[10px] text-apis-text2 mb-[7px] font-medium uppercase tracking-[0.07em]">Status chain</p>
                    <div class="flex items-center gap-1 flex-wrap">
                        @foreach ($request['chain'] as $index => $step)
                            @php
                                $stepStyle = $stepMap[$step['st']] ?? $stepMap['waiting'];
                            @endphp

                            <div class="flex items-center gap-1">
                                @if ($index > 0)
                                    <span class="text-[10px] text-apis-text3 mr-[2px]">›</span>
                                @endif

                                <div class="apis-step-pill" style="background: {{ $stepStyle['bg'] }};">
                                    <span class="apis-step-dot"
                                          style="background: {{ $stepStyle['dotBg'] }}; color: {{ $stepStyle['dotColor'] }};">
                                        {{ $stepStyle['symbol'] }}
                                    </span>
                                    <span class="text-[10px] whitespace-nowrap"
                                          style="color: {{ $stepStyle['color'] }}; font-weight: {{ $step['st'] === 'pending' ? '500' : '400' }};">
                                        {{ $step['role'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @include('partials.apis.simple-pagination', [
                'summary' => 'Showing ' . $this->showingFrom . '-' . $this->showingTo . ' of ' . $this->filteredRequests->count() . ' request' . ($this->filteredRequests->count() !== 1 ? 's' : ''),
                'page' => $page,
                'totalPages' => $this->totalPages,
            ])
        @endif
    </div>
</div>
