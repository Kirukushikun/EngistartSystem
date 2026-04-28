@php
    $attachments = $attachments ?? [];
@endphp

@if (! empty($attachments))
    <div class="mb-[14px]">
        <p class="text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Attachments</p>
        <div class="flex flex-col gap-[7px]">
            @foreach ($attachments as $attachment)
                <div class="flex items-start gap-[9px]">
                    <span class="apis-step-dot" style="background: var(--blue-bg); color: var(--blue);">↗</span>
                    <div class="flex-1 min-w-0 pt-[1px]">
                        <p class="text-[11px] text-apis-text2 m-0">{{ $attachment['label'] }}</p>
                        <a href="{{ $attachment['url'] }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="text-[12px] text-apis-text no-underline hover:underline break-all">
                            {{ $attachment['name'] }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
