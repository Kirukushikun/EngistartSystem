@props([
    'id',
    'status',
    'statusLabel',
    'isLate' => false,
    'title',
    'subtitle',
    'type',
    'purpose' => null,
    'chickin' => null,
    'cap' => null,
    'mtgDate' => null,
    'mtgTime' => null,
    'desc' => null,
    'budgetCategory' => null,
    'startDate' => null,
    'completionDate' => null,
    'jl' => null,
    'attachments' => [],
    'chain' => [],
    'submittedBy' => null,
    'submittedDate' => null,
    'remarkHistory' => [],
    'isPendingHere' => false,
    'textareaModel',
    'textareaPlaceholder',
])

<div class="apis-card" x-data="{ open: false }">
    <button type="button" @click="open = !open" class="w-full text-left p-[14px_18px] flex justify-between items-start gap-3" style="cursor: pointer;">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-[7px] mb-[5px] flex-wrap">
                <span class="font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $id }}</span>
                @include('partials.apis.request-status-badge', ['status' => $status, 'label' => $statusLabel])
                @if ($isLate)
                    <span class="text-[10px] px-[6px] py-[1px] rounded-[3px] font-medium" style="background: var(--amber-bg); color: var(--amber)">
                        LATE
                    </span>
                @endif
            </div>
            <p class="text-[14px] font-medium m-0 mb-[3px] overflow-hidden text-ellipsis whitespace-nowrap text-apis-text">{{ $title }}</p>
            <p class="text-[11px] text-apis-text2 m-0">{{ $subtitle }}</p>
        </div>
        <span class="text-[10px] text-apis-text3 flex-shrink-0 mt-[3px]">
            <span x-show="!open">▼</span>
            <span x-show="open">▲</span>
        </span>
    </button>

    <div x-cloak x-show="open" class="border-t p-[16px_18px]" style="border-color: var(--border)">
        <div class="mb-[14px] space-y-[8px] text-[12px] text-apis-text2">
            <div><span class="mr-1">Type:</span><span class="text-apis-text">{{ $type }}</span></div>
            @if ($purpose)<div><span class="mr-1">Purpose:</span><span class="text-apis-text">{{ $purpose }}</span></div>@endif
            @if ($chickin)<div><span class="mr-1">Chick-in:</span><span class="text-apis-text">{{ $chickin }}</span></div>@endif
            @if ($cap)<div><span class="mr-1">Capacity:</span><span class="text-apis-text">{{ $cap }}</span></div>@endif
            @if ($mtgDate)<div><span class="mr-1">Meeting:</span><span class="text-apis-text">{{ $mtgDate }} at {{ $mtgTime }}</span></div>@endif
        </div>

        @if ($desc)
            <p class="text-[12px] leading-[1.7] text-apis-text mb-[14px] border-l-2 pl-3" style="border-color: var(--border)">
                {{ $desc }}
            </p>
        @endif

        @include('partials.apis.request-detail-fields', [
            'budgetCategory' => $budgetCategory,
            'startDate' => $startDate,
            'completionDate' => $completionDate,
            'jl' => $jl,
        ])

        @include('partials.apis.attachments-section', [
            'attachments' => $attachments,
        ])

        @include('partials.apis.approval-chain', [
            'chain' => $chain,
            'submittedBy' => $submittedBy,
            'submittedDate' => $submittedDate,
        ])

        @include('partials.apis.remarks-section', [
            'history' => $remarkHistory,
            'showInput' => $isPendingHere,
            'textareaModel' => $textareaModel,
            'textareaPlaceholder' => $textareaPlaceholder,
        ])

        <div class="flex gap-2 flex-wrap">
            {{ $slot }}
        </div>
    </div>
</div>
