@php
    $history = $history ?? [];
    $showInput = $showInput ?? false;
    $textareaModel = $textareaModel ?? null;
    $textareaPlaceholder = $textareaPlaceholder ?? 'Add remarks here...';
@endphp

<div class="mb-[14px]">
    <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">
        Remarks
    </label>
    <div class="rounded-[12px] p-[12px]" style="background: var(--bg2); border: 0.5px solid var(--border)">
        @if (!empty($history))
            <details class="mb-[12px] group">
                <summary class="flex items-center justify-between gap-3 cursor-pointer rounded-[10px] px-[12px] py-[10px] text-[12px] text-apis-text" style="background: var(--bg); border: 0.5px solid var(--border)">
                    <span class="font-medium">Previous remarks ({{ count($history) }})</span>
                    <span class="text-[11px] text-apis-text2 group-open:hidden">Show</span>
                    <span class="text-[11px] text-apis-text2 hidden group-open:inline">Hide</span>
                </summary>

                <div class="space-y-[10px] mt-[10px]">
                    @foreach ($history as $entry)
                        @php
                            $entryStyle = match ($entry['tone']) {
                                'danger' => ['bg' => 'var(--red-bg)', 'color' => 'var(--red)', 'border' => 'var(--red-bd)'],
                                default => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)', 'border' => 'var(--blue-bd)'],
                            };
                        @endphp
                        <div class="rounded-[10px] p-[10px_12px]" style="background: {{ $entryStyle['bg'] }}; border: 0.5px solid {{ $entryStyle['border'] }};">
                            <div class="mb-1">
                                <p class="text-[12px] m-0 text-apis-text font-medium">{{ $entry['role'] }}@if($entry['actor']) · {{ $entry['actor'] }}@endif</p>
                                <p class="text-[11px] m-0" style="color: {{ $entryStyle['color'] }};">{{ $entry['label'] }}</p>
                            </div>
                            <p class="text-[12px] text-apis-text m-0 leading-[1.6]">{{ $entry['remarks'] }}</p>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif

        @if ($showInput && filled($textareaModel))
            <textarea wire:model.defer="{{ $textareaModel }}" class="apis-remarks-control" placeholder="{{ $textareaPlaceholder }}"></textarea>
        @endif
    </div>
</div>
