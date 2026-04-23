<div class="min-h-[60vh] w-full flex items-center justify-center px-2">
    <div class="flex w-full max-w-[480px] flex-col items-center text-center gap-3 mx-auto">
        <div class="w-[52px] h-[52px] rounded-full flex items-center justify-center text-[22px]" style="background: var(--green-bg); color: var(--green)">✓</div>
        <p class="text-[16px] font-medium text-apis-text">Change request submitted</p>
        <p class="text-[12px] text-apis-text2 leading-[1.6]">Your request has been forwarded to VP Gen Services for review. IT Admin will be notified upon approval to implement the change.</p>
        <div class="w-full rounded-[8px] p-[12px_20px] text-[12px] text-left" style="background: var(--bg2)">
            <div class="flex justify-between mb-1"><span class="text-apis-text2">Request ID</span><span class="font-mono font-medium text-apis-text">{{ $submittedId }}</span></div>
            <div class="flex justify-between"><span class="text-apis-text2">Routing</span><span class="font-medium text-[11px] px-2 py-0.5 rounded" style="background: var(--amber-bg); color: var(--amber)">Pending VP Review</span></div>
        </div>
        <button type="button" wire:click="resetForm" class="rounded-[8px] px-4 py-2 text-[12px] font-medium" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text)">Submit another request</button>
    </div>
</div>
