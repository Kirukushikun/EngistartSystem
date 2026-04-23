<div class="p-6 overflow-y-auto h-full">
    @include('partials.apis.alert', ['type' => 'warn', 'message' => 'Status overrides are logged in the audit trail. Use only in exceptional circumstances and ensure proper authorization.'])

    @include('partials.apis.filter-toolbar', [
        'gridClass' => 'grid-cols-1 md:grid-cols-[minmax(0,1.6fr)_220px]',
        'fields' => [
            [
                'label' => 'Search',
                'type' => 'text',
                'placeholder' => 'Search by request ID, title, farm, requester, or status...',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live.debounce.300ms' => 'search'],
            ],
            [
                'label' => 'Current status',
                'type' => 'select',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live' => 'statusFilter'],
                'options' => array_merge([['value' => 'all', 'label' => 'All statuses']], $this->statusOptions),
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

    @forelse ($this->paginatedRequests as $request)
        <div class="rounded-[12px] p-[14px_18px] mb-3 flex justify-between items-center flex-wrap gap-3" style="border: 0.5px solid var(--border); background: var(--bg)">
            <div>
                <div class="flex gap-2 mb-1 items-center">
                    <span class="font-mono text-[11px] text-apis-text2">{{ $request['id'] }}</span>
                    @include('partials.apis.request-status-badge', ['status' => $request['status'], 'label' => $request['status_label']])
                </div>
                <p class="text-[13px] font-medium m-0 mb-0.5 text-apis-text">{{ $request['title'] }}</p>
                <p class="text-[11px] m-0 text-apis-text2">{{ $request['farm'] }}</p>
                <p class="text-[11px] m-0 mt-1 text-apis-text2">Requested by {{ $request['requestedBy'] }}</p>
            </div>
            <div class="flex gap-2 items-center">
                <select wire:model.live="overrideStatus.{{ $request['id'] }}" class="w-[160px] h-[34px] rounded-[8px] px-3 text-[12px]" style="border: 0.5px solid var(--border2); background: var(--bg); color: var(--text)">
                    @foreach ($this->overrideOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="confirmOverride(@js($request['id']))" class="text-[11px] px-4 py-2 rounded-[8px] font-medium" style="background: var(--amber-bg); color: var(--amber); border: 0.5px solid var(--amber-bd)">Override</button>
            </div>
        </div>
    @empty
        <div class="text-center py-[80px] text-[13px] text-apis-text2">No requests match the current filters.</div>
    @endforelse

    @include('partials.apis.simple-pagination', [
        'summary' => 'Showing ' . $this->showingFrom . '-' . $this->showingTo . ' of ' . $this->filteredRequests->count() . ' request' . ($this->filteredRequests->count() !== 1 ? 's' : ''),
        'page' => $page,
        'totalPages' => $this->totalPages,
    ])
</div>
