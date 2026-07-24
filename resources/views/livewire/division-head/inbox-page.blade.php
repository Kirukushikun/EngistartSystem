<div class="p-6 overflow-y-auto h-full">
    <div class="w-full">
        @include('partials.apis.filter-toolbar', [
            'gridClass' => 'grid-cols-1 md:grid-cols-[minmax(0,1.5fr)_180px_180px]',
            'fields' => [
                [
                    'label' => 'Search',
                    'type' => 'text',
                    'placeholder' => 'Search by request ID, title, farm, or requester...',
                    'class' => 'apis-toolbar-control w-full',
                    'attributes' => ['wire:model.live.debounce.300ms' => 'search'],
                ],
                [
                    'label' => 'Type',
                    'type' => 'select',
                    'class' => 'apis-toolbar-control w-full',
                    'attributes' => ['wire:model.live' => 'typeFilter'],
                    'options' => array_merge(
                        [['value' => 'all', 'label' => 'All types']],
                        array_map(fn ($type) => ['value' => $type, 'label' => $type], $this->typeOptions)
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

        @forelse ($this->paginatedInboxItems as $request)
            <x-apis.request-card
                :id="$request['id']"
                :status="$request['status']"
                :status-label="$request['statusLabel']"
                :is-late="$request['isLate']"
                :title="$request['title']"
                subtitle="{{ $request['farm'] }} · Needed {{ $request['needed'] }} · {{ $request['days'] }}d ahead · By {{ $request['by'] }}"
                :type="$request['type']"
                :purpose="$request['purpose']"
                :chickin="$request['chickin']"
                :cap="$request['cap']"
                :mtg-date="$request['mtgDate']"
                :mtg-time="$request['mtgTime']"
                :desc="$request['desc']"
                :budget-category="$request['budgetCategory']"
                :start-date="$request['startDate']"
                :completion-date="$request['completionDate']"
                :jl="$request['jl']"
                :attachments="$request['attachments']"
                :chain="$request['chain']"
                :submitted-by="$request['by']"
                :submitted-date="$request['submitted']"
                :remark-history="$request['remarkHistory']"
                :is-pending-here="$request['isPendingHere']"
                :textarea-model="'remarks.' . $request['id']"
                textarea-placeholder="Add your recommendation or rejection remarks here...">
                @if ($request['isPendingHere'])
                    <button type="button"
                            wire:click="confirmRecommend(@js($request['id']))"
                            class="apis-card-button font-medium"
                            style="background: var(--green-bg); color: var(--green); border: 0.5px solid var(--green-bd)">
                        Recommend for Approval
                    </button>
                    <button type="button"
                            wire:click="confirmReject(@js($request['id']))"
                            class="apis-card-button font-medium"
                            style="background: var(--red-bg); color: var(--red); border: 0.5px solid var(--red-bd)">
                        Reject
                    </button>
                @else
                    <p class="text-[11px] text-apis-text2 m-0">
                        Retained here for transparency after your action.
                    </p>
                @endif
            </x-apis.request-card>
        @empty
            <div class="text-center py-[80px] text-[13px] text-apis-text2">
                No requests match the current filters.
            </div>
        @endforelse

        @include('partials.apis.simple-pagination', [
            'summary' => 'Showing ' . $this->showingFrom . '-' . $this->showingTo . ' of ' . $this->filteredInboxItems->count() . ' request' . ($this->filteredInboxItems->count() !== 1 ? 's' : ''),
            'page' => $page,
            'totalPages' => $this->totalPages,
        ])
    </div>
</div>
