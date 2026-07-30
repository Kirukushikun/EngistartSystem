<div class="p-6 overflow-y-auto h-full" x-data="{ selected: null }">
    @include('partials.apis.filter-toolbar', [
        'gridClass' => 'grid-cols-1 md:grid-cols-[220px]',
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
        ],
    ])

    <p class="text-[12px] text-apis-text2 mt-3 mb-1">{{ $this->scopeNote }}</p>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_260px] gap-4 mt-3 items-start">
        {{-- ── TIMELINE ─────────────────────────────────────────── --}}
        <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
            @if ($this->timeline)
                <div class="overflow-x-auto">
                    <div class="min-w-[820px] grid relative" style="grid-template-columns: 220px 1fr">
                        {{-- Ruler --}}
                        <div class="px-[14px] py-[9px] text-[11px] font-medium text-apis-text2 sticky top-0" style="background: var(--bg2); border-bottom: 0.5px solid var(--border)"></div>
                        <div class="relative sticky top-0" style="background: var(--bg2); border-bottom: 0.5px solid var(--border); height: 34px">
                            @foreach ($this->timeline['months'] as $month)
                                <div class="absolute top-0 bottom-0 flex items-center text-[11px] text-apis-text2 px-2"
                                     style="left: {{ $month['left'] }}%; width: {{ $month['width'] }}%; border-left: 0.5px dashed var(--border)">
                                    {{ $month['label'] }}
                                </div>
                            @endforeach
                        </div>

                        {{-- Today marker spans the full grid height --}}
                        @if ($this->timeline['todayLeft'] !== null)
                            <div class="relative" style="grid-column: 2; grid-row: 1 / -1">
                                <div class="absolute top-0 bottom-0 z-[1]" style="left: {{ $this->timeline['todayLeft'] }}%; border-left: 1.5px solid var(--red)">
                                    <span class="absolute -top-[1px] -translate-x-1/2 -translate-y-full text-[9.5px] font-semibold px-[5px] py-px rounded whitespace-nowrap"
                                          style="color: var(--red); background: var(--red-bg); border: 0.5px solid var(--red-bd)">
                                        Today
                                    </span>
                                </div>
                            </div>
                        @endif

                        {{-- Farm groups --}}
                        @foreach ($this->timeline['farms'] as $group)
                            <div class="px-[14px] py-[7px] text-[11px] font-semibold uppercase tracking-wide text-apis-text3 col-span-2" style="border-top: 0.5px solid var(--border)">
                                {{ $group['farm'] }}
                            </div>

                            @foreach ($group['rows'] as $row)
                                @php
                                    $statusStyle = match ($row['status']) {
                                        'submitted' => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)', 'bd' => 'var(--blue-bd)'],
                                        'recommended' => ['bg' => 'var(--violet-bg)', 'color' => 'var(--violet)', 'bd' => 'var(--violet-bd)'],
                                        'vp_approved' => ['bg' => 'var(--indigo-bg)', 'color' => 'var(--indigo)', 'bd' => 'var(--indigo-bd)'],
                                        'noted' => ['bg' => 'var(--teal-bg)', 'color' => 'var(--teal)', 'bd' => 'var(--teal-bd)'],
                                        'jl_pending' => ['bg' => 'var(--amber-bg)', 'color' => 'var(--amber)', 'bd' => 'var(--amber-bd)'],
                                        'accepted', 'initialized' => ['bg' => 'var(--green-bg)', 'color' => 'var(--green)', 'bd' => 'var(--green-bd)'],
                                        default => ['bg' => 'var(--gray-bg)', 'color' => 'var(--text3)', 'bd' => 'var(--border2)'],
                                    };
                                @endphp
                                <div class="px-[14px] py-[9px] text-[12px] text-apis-text truncate" style="border-top: 0.5px solid var(--border)" title="{{ $row['title'] }}">
                                    {{ $row['title'] }}
                                </div>
                                <div class="relative" style="border-top: 0.5px solid var(--border); min-height: 46px">
                                    <button type="button"
                                            @click="selected = (selected === '{{ $row['id'] }}') ? null : '{{ $row['id'] }}'"
                                            class="absolute top-[8px] bottom-[8px] rounded-[7px] px-[9px] flex items-center text-[11.5px] font-medium truncate cursor-pointer transition"
                                            :style="`left:{{ $row['left'] }}%;width:{{ $row['width'] }}%;background:{{ $statusStyle['bg'] }};color:{{ $statusStyle['color'] }};border:0.5px {{ $row['isProjected'] ? 'dashed' : 'solid' }} {{ $statusStyle['bd'] }}; ${selected === '{{ $row['id'] }}' ? 'box-shadow: 0 0 0 2px var(--bg), 0 0 0 3.5px var(--text)' : ''}`">
                                        {{ $row['title'] }}
                                    </button>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-wrap gap-[14px] px-4 py-[11px]" style="border-top: 0.5px solid var(--border); background: var(--bg2)">
                    <div class="flex items-center gap-[6px] text-[11px] text-apis-text2"><span class="w-[10px] h-[10px] rounded-[3px]" style="background: var(--blue-bg); border: 0.5px solid var(--blue-bd)"></span>Submitted</div>
                    <div class="flex items-center gap-[6px] text-[11px] text-apis-text2"><span class="w-[10px] h-[10px] rounded-[3px]" style="background: var(--violet-bg); border: 0.5px solid var(--violet-bd)"></span>Recommended</div>
                    <div class="flex items-center gap-[6px] text-[11px] text-apis-text2"><span class="w-[10px] h-[10px] rounded-[3px]" style="background: var(--indigo-bg); border: 0.5px solid var(--indigo-bd)"></span>VP Approved</div>
                    <div class="flex items-center gap-[6px] text-[11px] text-apis-text2"><span class="w-[10px] h-[10px] rounded-[3px]" style="background: var(--teal-bg); border: 0.5px solid var(--teal-bd)"></span>Noted</div>
                    <div class="flex items-center gap-[6px] text-[11px] text-apis-text2"><span class="w-[10px] h-[10px] rounded-[3px]" style="background: var(--amber-bg); border: 0.5px solid var(--amber-bd)"></span>JL Under Review</div>
                    <div class="flex items-center gap-[6px] text-[11px] text-apis-text2"><span class="w-[10px] h-[10px] rounded-[3px]" style="background: var(--green-bg); border: 0.5px solid var(--green-bd)"></span>Accepted / Initialized</div>
                    <div class="flex items-center gap-[6px] text-[11px] text-apis-text2"><span class="w-[10px] h-[1.5px]" style="border-top: 1.5px dashed var(--text3)"></span>Projected (no actual dates yet)</div>
                    <div class="flex items-center gap-[6px] text-[11px] text-apis-text2"><span class="w-[10px] h-[1.5px]" style="border-top: 1.5px solid var(--red)"></span>Today</div>
                </div>
            @else
                <div class="px-4 py-[40px] text-center text-[12px] text-apis-text2">No projects to display yet.</div>
            @endif
        </div>

        {{-- ── DETAIL PANEL ─────────────────────────────────────── --}}
        <div class="rounded-[12px] p-4 lg:sticky lg:top-4" style="border: 0.5px solid var(--border); background: var(--bg)">
            <div x-show="selected === null" class="text-[12px] text-apis-text3 leading-[1.7]">
                Select a project on the timeline to see its full details here — dates, farm, requestor, and assigned engineer.
            </div>

            @foreach ($this->rows as $row)
                <div x-cloak x-show="selected === '{{ $row['id'] }}'">
                    <div class="font-mono text-[11px] text-apis-text3 mb-[3px]">{{ $row['id'] }}</div>
                    <div class="text-[14.5px] font-semibold leading-[1.35] mb-[10px] text-apis-text">{{ $row['title'] }}</div>

                    <div class="flex flex-wrap gap-[6px] mb-[14px]">
                        @include('partials.apis.request-status-badge', ['status' => $row['status'], 'label' => $row['statusLabel']])
                        @if ($row['budgetCategory'])
                            <span class="text-[10.5px] font-semibold px-2 py-0.5 rounded-[5px]" style="background: var(--violet-bg); color: var(--violet); border: 0.5px solid var(--violet-bd)">{{ $row['budgetCategory'] }}</span>
                        @endif
                        @if ($row['isProjected'])
                            <span class="text-[10.5px] font-semibold px-2 py-0.5 rounded-[5px]" style="background: var(--gray-bg); color: var(--text3); border: 0.5px solid var(--border2)">Projected</span>
                        @endif
                    </div>

                    <div class="flex flex-col gap-[9px] mb-3 text-[12px]">
                        <div class="flex justify-between gap-[10px]"><span class="text-apis-text2">Farm</span><span class="font-medium text-right">{{ $row['farm'] }}</span></div>
                        <div class="flex justify-between gap-[10px]"><span class="text-apis-text2">Submitted by</span><span class="font-medium text-right">{{ $row['by'] }}</span></div>
                        <div class="flex justify-between gap-[10px]"><span class="text-apis-text2">Assigned engineer</span><span class="font-medium text-right">{{ $row['assignedEngineer'] }}</span></div>
                    </div>
                    <hr class="my-3" style="border-color: var(--border)">
                    <div class="flex flex-col gap-[9px] mb-3 text-[12px]">
                        <div class="flex justify-between gap-[10px]"><span class="text-apis-text2">Project start</span><span class="font-mono font-medium text-right">{{ $row['startDate']->format('M j, Y') }}</span></div>
                        <div class="flex justify-between gap-[10px]"><span class="text-apis-text2">Completion</span><span class="font-mono font-medium text-right">{{ $row['completionDate']->format('M j, Y') }}</span></div>
                    </div>
                    @if ($row['purpose'])
                        <hr class="my-3" style="border-color: var(--border)">
                        <p class="text-[11px] text-apis-text3 leading-[1.6]">{{ $row['purpose'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
