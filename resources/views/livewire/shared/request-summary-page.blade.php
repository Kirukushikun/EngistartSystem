<div class="p-6 overflow-y-auto h-full" x-data="{ openId: null }">
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
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Requestor</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Budget Category</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Date of Request</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Acceptance Date</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Project Start Date</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Project Completion Date</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Requested Completion Date</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->paginatedRows as $row)
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td class="px-[14px] py-[9px] font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $row['id'] }}</td>
                            <td class="px-[14px] py-[9px]">@include('partials.apis.request-status-badge', ['status' => $row['status'], 'label' => $row['statusLabel']])</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $row['farm'] }}</td>
                            <td class="px-[14px] py-[9px] font-medium text-apis-text whitespace-nowrap">{{ $row['title'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $row['by'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $row['budgetCategory'] ?? '—' }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $row['dateOfRequest'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $row['acceptanceDate'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $row['projectStartDate'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $row['projectCompletionDate'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $row['requestedCompletionDate'] }}</td>
                            <td class="px-[14px] py-[9px] whitespace-nowrap">
                                <button type="button"
                                        @click="openId = '{{ $row['id'] }}'"
                                        class="text-[11px] font-medium px-[10px] py-[5px] rounded-[7px]"
                                        style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd)">
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td colspan="12" class="px-[14px] py-[32px] text-center text-[12px] text-apis-text2">No requests match the current filters.</td>
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

    @foreach ($this->paginatedRows as $row)
        <div x-cloak x-show="openId === '{{ $row['id'] }}'" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <button type="button" @click="openId = null" class="absolute inset-0 bg-black/40"></button>

            <div class="relative w-full max-w-[640px] max-h-[85vh] overflow-y-auto rounded-[14px] bg-apis-bg shadow-xl" style="border: 0.5px solid var(--border2)">
                <div class="p-[18px_20px] border-b flex items-start justify-between gap-3" style="border-color: var(--border)">
                    <div class="min-w-0">
                        <div class="flex items-center gap-[7px] mb-[5px] flex-wrap">
                            <span class="font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $row['id'] }}</span>
                            @include('partials.apis.request-status-badge', ['status' => $row['status'], 'label' => $row['statusLabel']])
                        </div>
                        <h3 class="text-[15px] font-semibold text-apis-text m-0">{{ $row['title'] }}</h3>
                        <p class="text-[11px] text-apis-text2 mt-1 m-0">{{ $row['farm'] }} · By {{ $row['by'] }}</p>
                    </div>
                    <button type="button" @click="openId = null" class="text-[11px] text-apis-text2 flex-shrink-0">✕</button>
                </div>

                <div class="p-[18px_20px]">
                    <div class="mb-[14px] space-y-[8px] text-[12px] text-apis-text2">
                        <div><span class="mr-1">Type:</span><span class="text-apis-text">{{ $row['type'] }}</span></div>
                        @if ($row['purpose'])<div><span class="mr-1">Purpose:</span><span class="text-apis-text">{{ $row['purpose'] }}</span></div>@endif
                    </div>

                    @if ($row['desc'])
                        <p class="text-[12px] leading-[1.7] text-apis-text mb-[14px] border-l-2 pl-3" style="border-color: var(--border)">
                            {{ $row['desc'] }}
                        </p>
                    @endif

                    @include('partials.apis.request-detail-fields', [
                        'budgetCategory' => $row['budgetCategory'],
                        'startDate' => $row['projectStartDate'],
                        'completionDate' => $row['projectCompletionDate'],
                        'jl' => $row['jl'],
                    ])

                    @include('partials.apis.attachments-section', [
                        'attachments' => $row['attachments'],
                    ])
                </div>
            </div>
        </div>
    @endforeach
</div>
