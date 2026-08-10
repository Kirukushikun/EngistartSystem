<div>
    @if (! empty($notices))
        <div class="fixed inset-0 z-[95] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40"></div>

            <div class="relative w-full max-w-lg rounded-[14px] bg-apis-bg shadow-xl max-h-[85vh] flex flex-col" style="border: 0.5px solid var(--border2)">
                <div class="p-[18px_20px] border-b flex-shrink-0" style="border-color: var(--border)">
                    <div class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.08em]" style="background: var(--red-bg); color: var(--red); border: 0.5px solid var(--red-bd);">
                        Needs your attention
                    </div>
                    <h3 class="mt-3 text-[16px] font-semibold text-apis-text">
                        {{ count($notices) === 1 ? 'A request needs your attention' : count($notices) . ' requests need your attention' }}
                    </h3>
                    <p class="mt-1 text-[13px] leading-[1.6] text-apis-text2">
                        {{ count($notices) === 1 ? 'The following request was not approved.' : 'The following requests were not approved.' }}
                        Review the remarks below and resubmit from My Requests.
                    </p>
                </div>

                <div class="p-[18px_20px] space-y-3 overflow-y-auto">
                    @foreach ($notices as $notice)
                        <div class="rounded-[12px] p-[12px_14px]" style="background: var(--bg2); border: 0.5px solid var(--border)">
                            <div class="flex items-start justify-between gap-3 mb-1.5">
                                <div class="min-w-0">
                                    <span class="font-mono text-[11px] text-apis-text2">{{ $notice['code'] }}</span>
                                    <p class="text-[13px] font-medium text-apis-text m-0 truncate">{{ $notice['title'] }}</p>
                                </div>
                                <span class="flex-shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full" style="background: var(--red-bg); color: var(--red); border: 0.5px solid var(--red-bd)">
                                    {{ $notice['statusLabel'] }}
                                </span>
                            </div>
                            <p class="text-[12px] leading-[1.6] text-apis-text2 m-0">{{ $notice['remarks'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-2 p-[16px_20px] border-t flex-shrink-0" style="border-color: var(--border)">
                    <button type="button" wire:click="dismiss" class="apis-card-button">Dismiss</button>
                    <button type="button"
                            wire:click="reviewAndDismiss"
                            class="apis-card-button font-medium"
                            style="background: var(--red-bg); color: var(--red); border: 0.5px solid var(--red-bd);">
                        Review in My Requests
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
