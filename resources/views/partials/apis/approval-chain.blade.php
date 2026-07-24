@php
    $stepMap = [
        'done' => ['bg' => 'var(--green-bg)', 'color' => 'var(--green)', 'dotBg' => 'var(--green-bg)', 'dotColor' => 'var(--green)', 'symbol' => '✓'],
        'pending' => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)', 'dotBg' => 'var(--blue-bg)', 'dotColor' => 'var(--blue)', 'symbol' => '●'],
        'rejected' => ['bg' => 'var(--red-bg)', 'color' => 'var(--red)', 'dotBg' => 'var(--red-bg)', 'dotColor' => 'var(--red)', 'symbol' => '✕'],
        'waiting' => ['bg' => 'var(--gray-bg)', 'color' => 'var(--text3)', 'dotBg' => 'var(--gray-bg)', 'dotColor' => 'var(--text3)', 'symbol' => '○'],
    ];
@endphp

<div class="mb-[14px]">
    <p class="text-[10px] text-apis-text2 mb-[7px] font-medium uppercase tracking-[0.07em]">{{ $heading ?? 'Approval Chain' }}</p>

    @if ($submittedBy ?? null)
        <p class="text-[11px] text-apis-text2 mb-[7px] m-0">
            Submitted by <span class="text-apis-text font-medium">{{ $submittedBy }}</span>
            @if ($submittedDate ?? null)
                · {{ $submittedDate }}
            @endif
        </p>
    @endif

    <div class="flex items-center gap-1 flex-wrap">
        @foreach ($chain as $index => $step)
            <div class="flex items-center gap-1">
                @if ($index > 0)
                    <span class="text-[10px] text-apis-text3 mr-[2px]">›</span>
                @endif

                @if (($step['kind'] ?? 'step') === 'marker')
                    <span class="text-[10px] whitespace-nowrap px-1" style="color: var(--text3); font-style: italic;">
                        — {{ $step['label'] }}
                    </span>
                @else
                    @php
                        $stepStyle = $stepMap[$step['state']] ?? $stepMap['waiting'];
                    @endphp
                    <div class="apis-step-pill" style="background: {{ $stepStyle['bg'] }};">
                        <span class="apis-step-dot" style="background: {{ $stepStyle['dotBg'] }}; color: {{ $stepStyle['dotColor'] }};">
                            {{ $stepStyle['symbol'] }}
                        </span>
                        <span class="text-[10px] whitespace-nowrap" style="color: {{ $stepStyle['color'] }}; font-weight: {{ $step['state'] === 'pending' ? '500' : '400' }};">
                            {{ $step['role'] }}
                        </span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
