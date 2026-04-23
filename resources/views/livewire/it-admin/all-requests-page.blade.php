<div class="p-6 overflow-y-auto h-full">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-5">
        <div class="rounded-[12px] p-[14px_16px]" style="background: var(--bg2); border: 0.5px solid var(--border)"><p class="text-[11px] text-apis-text2 m-0 mb-1">Total</p><p class="text-[26px] font-medium m-0 text-apis-text">{{ $this->totalCount }}</p></div>
        <div class="rounded-[12px] p-[14px_16px]" style="background: var(--bg2); border: 0.5px solid var(--border)"><p class="text-[11px] text-apis-text2 m-0 mb-1">In Progress</p><p class="text-[26px] font-medium m-0 text-apis-text">{{ $this->inProgressCount }}</p></div>
        <div class="rounded-[12px] p-[14px_16px]" style="background: var(--bg2); border: 0.5px solid var(--border)"><p class="text-[11px] text-apis-text2 m-0 mb-1">Completed</p><p class="text-[26px] font-medium m-0 text-apis-text">{{ $this->completedCount }}</p></div>
        <div class="rounded-[12px] p-[14px_16px]" style="background: var(--bg2); border: 0.5px solid var(--border)"><p class="text-[11px] text-apis-text2 m-0 mb-1">Late Filings</p><p class="text-[26px] font-medium m-0 text-apis-text">{{ $this->lateFilingsCount }}</p></div>
    </div>

    @include('partials.apis.filter-toolbar', [
        'gridClass' => 'grid-cols-1 md:grid-cols-[minmax(0,1.8fr)_220px_220px]',
        'fields' => [
            [
                'label' => 'Search',
                'type' => 'text',
                'placeholder' => 'Search by request ID, title, farm, requester, type, routing, or status...',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live.debounce.300ms' => 'search'],
            ],
            [
                'label' => 'Status',
                'type' => 'select',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live' => 'statusFilter'],
                'options' => array_merge([['value' => 'all', 'label' => 'All statuses']], $this->statusOptions),
            ],
            [
                'label' => 'Sort',
                'type' => 'select',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live' => 'sortBy'],
                'options' => [
                    ['value' => 'latest', 'label' => 'Latest submitted'],
                    ['value' => 'oldest', 'label' => 'Earliest submitted'],
                    ['value' => 'needed_asc', 'label' => 'Date needed nearest'],
                    ['value' => 'needed_desc', 'label' => 'Date needed farthest'],
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

    <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-[12px]">
                <thead>
                    <tr style="background: var(--bg2)">
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">ID</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Project Title</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Farm</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">By</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Date Needed</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Days</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Routing</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->paginatedRequests as $request)
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td class="px-[14px] py-[9px] font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['id'] }}</td>
                            <td class="px-[14px] py-[9px] font-medium text-apis-text whitespace-nowrap">{{ $request['title'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['farm'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['by'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $request['needed'] }}</td>
                            <td class="px-[14px] py-[9px]"><span class="font-medium" style="color: {{ ($request['days'] ?? 0) < 0 ? 'var(--red)' : 'var(--green)' }}">{{ $request['days'] !== null ? $request['days'] . 'd' : '—' }}</span></td>
                            <td class="px-[14px] py-[9px]"><span class="text-[10px] px-2 py-0.5 rounded" style="background: var(--blue-bg); color: var(--blue)">{{ $request['routing'] }}</span></td>
                            <td class="px-[14px] py-[9px]">@include('partials.apis.request-status-badge', ['status' => $request['status'], 'label' => $request['status_label']])</td>
                        </tr>
                    @empty
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td colspan="8" class="px-[14px] py-[32px] text-center text-[12px] text-apis-text2">No requests match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.apis.simple-pagination', [
        'summary' => 'Showing ' . $this->showingFrom . '-' . $this->showingTo . ' of ' . $this->filteredRequests->count() . ' request' . ($this->filteredRequests->count() !== 1 ? 's' : ''),
        'page' => $page,
        'totalPages' => $this->totalPages,
    ])
</div>
