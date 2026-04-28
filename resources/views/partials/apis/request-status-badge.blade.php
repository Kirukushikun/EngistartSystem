@php
    $resolvedStatus = $status ?? null;
    $resolvedLabel = $label ?? ($resolvedStatus ? str_replace('_', ' ', str($resolvedStatus)->title()) : 'Unknown');

    $statusStyle = match ($resolvedStatus) {
        'submitted' => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)'],
        'pending_vp' => ['bg' => 'var(--amber-bg)', 'color' => 'var(--amber)'],
        'pending_it' => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)'],
        'recommended' => ['bg' => 'var(--violet-bg)', 'color' => 'var(--violet)'],
        'vp_approved', 'approved' => ['bg' => 'var(--indigo-bg)', 'color' => 'var(--indigo)'],
        'implemented' => ['bg' => 'var(--green-bg)', 'color' => 'var(--green)'],
        'noted' => ['bg' => 'var(--teal-bg)', 'color' => 'var(--teal)'],
        'approved_late', 'accepted' => ['bg' => 'var(--green-bg)', 'color' => 'var(--green)'],
        'returned', 'returned_to_requestor', 'rejected', 'rejected_late', 'cr_rejected' => ['bg' => 'var(--red-bg)', 'color' => 'var(--red)'],
        'withdrawn' => ['bg' => 'var(--red-bg)', 'color' => 'var(--red)'],
        default => ['bg' => 'var(--gray-bg)', 'color' => 'var(--text3)'],
    };
@endphp

<span class="text-[11px] px-2 py-0.5 rounded font-medium"
      style="background: {{ $statusStyle['bg'] }}; color: {{ $statusStyle['color'] }}">
    {{ $resolvedLabel }}
</span>
