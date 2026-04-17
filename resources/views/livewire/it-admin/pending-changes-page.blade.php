<div class="p-6 overflow-y-auto h-full">
    @include('partials.apis.alert', ['type' => 'info', 'message' => '1 VP-approved change ready for implementation.'])

    @if ($message)
        @include('partials.apis.alert', ['type' => 'info', 'message' => $message])
    @endif

    @foreach ($this->pendingChanges as $change)
        <div class="rounded-[12px] overflow-hidden mb-4" style="border: 0.5px solid var(--blue-bd); background: var(--bg)">
            <div class="p-[14px_18px] flex justify-between items-center gap-3" style="background: var(--blue-bg); border-bottom: 0.5px solid var(--blue-bd)">
                <div>
                    <p class="text-[13px] font-medium m-0 text-apis-text">{{ $change['setting'] }}</p>
                    <p class="text-[11px] m-0 text-apis-text2">{{ $change['id'] }}</p>
                </div>
                <span class="text-[10px] px-2 py-0.5 rounded font-medium" style="background: var(--bg); color: var(--blue)">Ready for IT</span>
            </div>
            <div class="p-[16px_18px] space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-[12px]">
                    <div class="rounded-[8px] p-3" style="background: var(--bg2)"><span class="text-apis-text2">Old value:</span><span class="ml-1 font-medium" style="color: var(--red)">{{ $change['oldVal'] }}</span></div>
                    <div class="rounded-[8px] p-3" style="background: var(--bg2)"><span class="text-apis-text2">New value:</span><span class="ml-1 font-medium" style="color: var(--green)">{{ $change['newVal'] }}</span></div>
                </div>
                <div class="text-[12px]"><span class="text-apis-text2">Requested by:</span><span class="ml-1 text-apis-text">{{ $change['requestedBy'] }}</span></div>
                <div class="text-[12px]"><span class="text-apis-text2">Justification:</span><p class="mt-1 mb-0 text-apis-text leading-[1.6]">{{ $change['reason'] }}</p></div>
                <div><button type="button" wire:click="implement(@js($change['id']))" class="text-[12px] px-4 py-2 rounded-[8px] font-medium" style="background: var(--green-bg); color: var(--green); border: 0.5px solid var(--green-bd)">Implement Change</button></div>
            </div>
        </div>
    @endforeach
</div>
