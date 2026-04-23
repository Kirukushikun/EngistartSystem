<div class="p-6 overflow-y-auto h-full">
    @include('partials.apis.filter-toolbar', [
        'gridClass' => 'grid-cols-1 md:grid-cols-[minmax(0,1.6fr)_190px_190px_190px]',
        'fields' => [
            [
                'label' => 'Search',
                'type' => 'text',
                'placeholder' => 'Search by user, role, action, request ID, title, or details...',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live.debounce.300ms' => 'search'],
            ],
            [
                'label' => 'Action',
                'type' => 'select',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live' => 'actionFilter'],
                'options' => array_merge([['value' => 'all', 'label' => 'All actions']], $this->actionOptions),
            ],
            [
                'label' => 'Role',
                'type' => 'select',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live' => 'roleFilter'],
                'options' => array_merge([['value' => 'all', 'label' => 'All roles']], $this->roleOptions),
            ],
            [
                'label' => 'Sort',
                'type' => 'select',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live' => 'sortBy'],
                'options' => [
                    ['value' => 'latest', 'label' => 'Latest first'],
                    ['value' => 'oldest', 'label' => 'Oldest first'],
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
        <div class="px-[18px] py-[12px] flex justify-between items-center" style="border-bottom: 0.5px solid var(--border); background: var(--bg2)">
            <span class="text-[13px] font-medium text-apis-text">Activity Log</span>
            <span class="text-[11px] text-apis-text2">{{ $this->filteredLogs->count() }} matching record{{ $this->filteredLogs->count() !== 1 ? 's' : '' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-[12px]">
                <thead>
                    <tr style="background: var(--bg2)">
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Timestamp</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">User</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Role</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Action</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Request ID</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->paginatedLogs as $log)
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td class="px-[14px] py-[9px] font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $log['ts'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text whitespace-nowrap">{{ $log['user'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $log['role'] }}</td>
                            <td class="px-[14px] py-[9px]"><span class="text-[11px] px-2 py-0.5 rounded font-medium" style="background: var(--blue-bg); color: var(--blue)">{{ $log['action'] }}</span></td>
                            <td class="px-[14px] py-[9px] font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $log['id'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2">{{ $log['note'] }}</td>
                        </tr>
                    @empty
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td colspan="6" class="px-[14px] py-[32px] text-center text-[12px] text-apis-text2">No audit entries match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.apis.simple-pagination', [
        'summary' => 'Showing ' . $this->showingFrom . '-' . $this->showingTo . ' of ' . $this->filteredLogs->count() . ' audit record' . ($this->filteredLogs->count() !== 1 ? 's' : ''),
        'page' => $page,
        'totalPages' => $this->totalPages,
    ])
</div>
