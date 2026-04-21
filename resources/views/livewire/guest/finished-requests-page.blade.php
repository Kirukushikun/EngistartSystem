<div class="p-6 overflow-y-auto h-full">
    @include('partials.apis.alert', ['type' => 'info', 'message' => 'This guest view only displays accepted requests. Rejected, submitted, recommended, approved, noted, and other in-progress records are hidden.'])

    @include('partials.apis.filter-toolbar', [
        'background' => 'var(--bg)',
        'gridClass' => 'grid-cols-1',
        'fields' => [
            [
                'label' => 'Search',
                'type' => 'text',
                'placeholder' => 'Search by ID, title, farm, or requester...',
                'class' => 'apis-toolbar-control',
                'attributes' => ['wire:model.live.debounce.300ms' => 'search'],
            ],
        ],
    ])

    @forelse ($this->finishedRequests as $request)
        <div class="apis-guest-card" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="w-full text-left p-[14px_18px] flex justify-between items-start gap-3" style="cursor: pointer;">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-[7px] mb-[5px] flex-wrap">
                        <span class="font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['id'] }}</span>
                        @include('partials.apis.request-status-badge', ['status' => $request['status'], 'label' => 'Accepted'])
                    </div>
                    <p class="text-[14px] font-medium m-0 mb-[3px] overflow-hidden text-ellipsis whitespace-nowrap text-apis-text">{{ $request['title'] }}</p>
                    <p class="text-[11px] text-apis-text2 m-0">{{ $request['farm'] }} · Needed {{ $request['needed'] }} · Requested by {{ $request['by'] }} · Finished {{ $request['completedAt'] }}</p>
                </div>
                <span class="text-[10px] text-apis-text3 flex-shrink-0 mt-[3px]"><span x-show="!open">▼</span><span x-show="open">▲</span></span>
            </button>

            <div x-cloak x-show="open" class="border-t p-[16px_18px]" style="border-color: var(--border)">
                <div class="mb-[14px] space-y-[8px] text-[12px] text-apis-text2">
                    <div><span class="mr-1">Type:</span><span class="text-apis-text">{{ $request['type'] }}</span></div>
                    <div><span class="mr-1">Purpose:</span><span class="text-apis-text">{{ $request['purpose'] }}</span></div>
                    @if ($request['chickin'])
                        <div><span class="mr-1">Chick-in:</span><span class="text-apis-text">{{ $request['chickin'] }}</span></div>
                    @endif
                    @if ($request['cap'])
                        <div><span class="mr-1">Capacity:</span><span class="text-apis-text">{{ $request['cap'] }}</span></div>
                    @endif
                    @if ($request['mtgDate'])
                        <div><span class="mr-1">Meeting:</span><span class="text-apis-text">{{ $request['mtgDate'] }} at {{ $request['mtgTime'] }}</span></div>
                    @endif
                </div>

                <p class="text-[12px] leading-[1.7] text-apis-text border-l-2 pl-3 m-0" style="border-color: var(--border)">
                    {{ $request['desc'] }}
                </p>
            </div>
        </div>
    @empty
        <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
            <div class="px-[14px] py-8 text-center text-[12px] text-apis-text2">No finished requests match the current filters.</div>
        </div>
    @endforelse
</div>
