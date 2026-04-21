<div>
    <div class="p-6 overflow-y-auto h-full">
        <div class="w-full">
            @include('partials.apis.alert', [
                'type' => 'info',
                'message' => 'This page shows requests and decisions already acted on by ' . $this->roleLabel . '.',
            ])

            @include('partials.apis.filter-toolbar', [
                'gridClass' => 'grid-cols-1 md:grid-cols-[minmax(0,1.5fr)_190px_190px]',
                'fields' => [
                    [
                        'label' => 'Search',
                        'type' => 'text',
                        'placeholder' => 'Search by request ID, title, farm, requester, or action...',
                        'class' => 'apis-toolbar-control w-full',
                        'attributes' => ['wire:model.live.debounce.300ms' => 'search'],
                    ],
                    [
                        'label' => 'Action',
                        'type' => 'select',
                        'class' => 'apis-toolbar-control w-full',
                        'attributes' => ['wire:model.live' => 'actionFilter'],
                        'options' => array_merge(
                            [['value' => 'all', 'label' => 'All actions']],
                            $this->actionOptions
                        ),
                    ],
                    [
                        'label' => 'Sort',
                        'type' => 'select',
                        'class' => 'apis-toolbar-control w-full',
                        'attributes' => ['wire:model.live' => 'sortBy'],
                        'options' => [
                            ['value' => 'latest', 'label' => 'Latest acted on'],
                            ['value' => 'acted_asc', 'label' => 'Earliest acted on'],
                            ['value' => 'requested_desc', 'label' => 'Latest submitted'],
                            ['value' => 'requested_asc', 'label' => 'Earliest submitted'],
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

            @forelse ($this->paginatedHistoryItems as $item)
                <div class="apis-card">
                    <div class="p-[14px_18px]">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-[7px] mb-[5px] flex-wrap">
                                    <span class="font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $item['id'] }}</span>
                                    @include('partials.apis.request-status-badge', ['status' => $item['action_key'], 'label' => $item['action']])
                                </div>
                                <p class="text-[14px] font-medium m-0 mb-[3px] overflow-hidden text-ellipsis whitespace-nowrap text-apis-text">{{ $item['title'] }}</p>
                                <p class="text-[11px] text-apis-text2 m-0">{{ $item['farm'] }} · {{ $item['type'] }} · Requested by {{ $item['requestedBy'] }}</p>
                                <p class="text-[11px] text-apis-text2 m-0 mt-1">Requested {{ $item['requested_at'] }} · Acted {{ $item['acted_at'] }} by {{ $item['actor'] }}</p>
                                <p class="text-[11px] text-apis-text m-0 mt-2 leading-[1.6]">{{ $item['remarks'] }}</p>
                            </div>
                            @include('partials.apis.request-status-badge', ['status' => $item['current_status'], 'label' => $item['current_status_label']])
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-[80px] text-[13px] text-apis-text2">No history items match the current filters.</div>
            @endforelse

            @include('partials.apis.simple-pagination', [
                'summary' => 'Showing ' . $this->showingFrom . '-' . $this->showingTo . ' of ' . $this->filteredHistoryItems->count() . ' history item' . ($this->filteredHistoryItems->count() !== 1 ? 's' : ''),
                'page' => $page,
                'totalPages' => $this->totalPages,
            ])
        </div>
    </div>
</div>
