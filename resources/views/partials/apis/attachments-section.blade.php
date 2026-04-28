@php
    $attachments = $attachments ?? [];
@endphp

@if (! empty($attachments))
    <div class="mb-[14px] rounded-[10px] p-[10px_12px]" style="background: var(--bg2); border: 0.5px solid var(--border)">
        <p class="text-[10px] text-apis-text2 mb-[7px] font-medium uppercase tracking-[0.07em]">Attachments</p>
        <div class="space-y-2">
            @foreach ($attachments as $attachment)
                <div class="rounded-[8px] p-[8px_10px] flex items-center justify-between gap-3 flex-wrap" style="background: var(--bg); border: 0.5px solid var(--border)">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-apis-text m-0">{{ $attachment['label'] }}</p>
                        <p class="text-[11px] text-apis-text2 m-0 truncate">{{ $attachment['name'] }}</p>
                    </div>
                    <a href="{{ $attachment['url'] }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="apis-card-button text-[11px] no-underline"
                       style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd)">
                        View File
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif
