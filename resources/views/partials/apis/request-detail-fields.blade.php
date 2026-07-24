<div class="mb-[14px]">
    <p class="text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Request Details</p>
    <div class="flex flex-col gap-[7px]">
        @if ($requestorRole ?? null)
            <div class="flex items-center gap-[9px]">
                <span class="apis-step-dot mt-[1px]" style="background: var(--indigo-bg); color: var(--indigo);">👤</span>
                <span class="text-[12px] text-apis-text">Requestor role: <span class="text-apis-text2">{{ $requestorRole }}</span></span>
            </div>
        @endif

        @if ($budgetCategory ?? null)
            <div class="flex items-center gap-[9px]">
                <span class="apis-step-dot mt-[1px]" style="background: var(--teal-bg); color: var(--teal);">₱</span>
                <span class="text-[12px] text-apis-text">Budget category: <span class="text-apis-text2">{{ $budgetCategory }}</span></span>
            </div>
        @endif

        @if (($startDate ?? null) || ($completionDate ?? null))
            <div class="flex items-center gap-[9px]">
                <span class="apis-step-dot mt-[1px]" style="background: var(--violet-bg); color: var(--violet);">📅</span>
                <span class="text-[12px] text-apis-text">
                    Timeline: <span class="text-apis-text2">{{ $startDate ?? '—' }} → {{ $completionDate ?? '—' }}</span>
                </span>
            </div>
        @endif
    </div>

    @if ($jl ?? null)
        <div class="mt-[10px] rounded-[10px] p-[10px_12px]" style="background: var(--amber-bg); border: 0.5px solid var(--amber-bd)">
            <p class="text-[10px] mb-[7px] font-medium uppercase tracking-[0.07em]" style="color: var(--amber)">Justification Letter</p>
            <div class="space-y-[6px] text-[12px] text-apis-text">
                <div><span class="text-apis-text2">Reason for delay:</span> {{ $jl['delayReason'] ?? '—' }}</div>
                <div><span class="text-apis-text2">Estimated turnover date:</span> {{ $jl['estimatedTurnoverDate'] ?? '—' }}</div>
                <div><span class="text-apis-text2">Implication if not completed:</span> {{ $jl['implicationIfNotCompleted'] ?? '—' }}</div>
                <div><span class="text-apis-text2">Estimated financial opportunity loss:</span> {{ $jl['estimatedFinancialOpportunityLoss'] ?? '—' }}</div>
            </div>
        </div>
    @endif
</div>
