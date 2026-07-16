<div class="p-6 overflow-y-auto h-full">
    @include('partials.apis.filter-toolbar', [
        'gridClass' => 'grid-cols-1 md:grid-cols-[220px_170px_170px_170px]',
        'fields' => [
            [
                'label' => 'Farm',
                'type' => 'select',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live' => 'farmFilter'],
                'options' => array_merge(
                    [['value' => 'all', 'label' => 'All farms']],
                    array_map(fn ($farm) => ['value' => $farm, 'label' => $farm], $this->farmOptions)
                ),
            ],
            [
                'label' => 'From',
                'type' => 'date',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live' => 'dateFrom'],
            ],
            [
                'label' => 'To',
                'type' => 'date',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live' => 'dateTo'],
            ],
            [
                'label' => 'Sort by date',
                'type' => 'select',
                'class' => 'apis-toolbar-control w-full',
                'attributes' => ['wire:model.live' => 'sortDirection'],
                'options' => [
                    ['value' => 'asc', 'label' => 'Earliest → Latest'],
                    ['value' => 'desc', 'label' => 'Latest → Earliest'],
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
                    ['value' => '10', 'label' => '10'],
                    ['value' => '15', 'label' => '15'],
                    ['value' => '25', 'label' => '25'],
                ],
            ],
        ],
    ])

    <div class="rounded-[12px] overflow-hidden mt-4" style="border: 0.5px solid var(--border); background: var(--bg)">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-[12px]">
                <thead>
                    <tr style="background: var(--bg2)">
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Code</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Status</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Farm</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Project Description based on CAPEX</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Date of Request</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Acceptance Date</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Project Start Date</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Project Completion Date</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Requested Completion Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->paginatedRows as $row)
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td class="px-[14px] py-[9px] font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $row['id'] }}</td>
                            <td class="px-[14px] py-[9px]">@include('partials.apis.request-status-badge', ['status' => $row['status'], 'label' => $row['statusLabel']])</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $row['farm'] }}</td>
                            <td class="px-[14px] py-[9px] font-medium text-apis-text whitespace-nowrap">{{ $row['title'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $row['dateOfRequest'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $row['acceptanceDate'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $row['projectStartDate'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $row['projectCompletionDate'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $row['requestedCompletionDate'] }}</td>
                        </tr>
                    @empty
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td colspan="9" class="px-[14px] py-[32px] text-center text-[12px] text-apis-text2">No requests match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.apis.simple-pagination', [
        'summary' => 'Showing ' . $this->showingFrom . '-' . $this->showingTo . ' of ' . $this->rows->count() . ' request' . ($this->rows->count() !== 1 ? 's' : ''),
        'page' => $page,
        'totalPages' => $this->totalPages,
    ])
</div>
