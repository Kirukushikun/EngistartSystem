@extends('layouts.app')

@section('title', 'My Requests | EngiStart')
@section('header', 'My Requests')
@section('subheader', 'Track the status of your submitted project requests.')

@section('sidebar')
    <a href="{{ route('farm-manager.requests.new') }}"
       class="flex items-center rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text">
        New Request
    </a>
    <a href="{{ route('farm-manager.requests.index') }}"
       class="flex items-center rounded-md px-3 py-2 text-sm font-medium bg-apis-bg text-apis-text"
       style="border: 0.5px solid var(--border2)">
        My Requests
    </a>
@endsection

@section('sidebarFooter')
    <p class="mb-1 text-[10px] text-apis-text3">Signed in as</p>
    <p class="text-xs font-medium leading-tight text-apis-text">Jose Santos</p>
    <p class="mt-0.5 text-[11px] text-apis-blue">Farm Manager</p>
@endsection

@section('content')
<div class="p-6 overflow-y-auto h-full">
    <div class="max-w-[760px]">
        <style>
            .apis-filter-chip {
                font-size: 11px;
                padding: 4px 12px;
                border-radius: 9999px;
                border: 0.5px solid transparent;
                color: var(--text);
                background: transparent;
                transition: background 0.12s, border-color 0.12s, color 0.12s;
            }

            .apis-filter-chip.is-active {
                background: var(--bg2);
                border-color: var(--border);
                font-weight: 500;
            }

            .apis-badge {
                font-size: 11px;
                padding: 2px 8px;
                border-radius: 4px;
                font-weight: 500;
                white-space: nowrap;
            }

            .apis-step-pill {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 3px 8px;
                border-radius: 9999px;
            }

            .apis-step-dot {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 14px;
                height: 14px;
                border-radius: 9999px;
                font-size: 8px;
                font-weight: 600;
                flex-shrink: 0;
            }
        </style>

        <div class="flex gap-1.5 mb-4 flex-wrap">
            @foreach (['all' => 'All', 'submitted' => 'submitted', 'late_pending' => 'late pending', 'accepted' => 'accepted', 'rejected' => 'rejected'] as $value => $label)
                <button type="button"
                        wire:click="setFilter('{{ $value }}')"
                        class="apis-filter-chip {{ $filter === $value ? 'is-active' : '' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($this->shownRequests->isEmpty())
            <div class="text-center py-[60px] text-[13px] text-apis-text2">
                No requests match this filter.
            </div>
        @else
            @foreach ($this->shownRequests as $request)
                @php
                    $statusMap = [
                        'submitted' => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)', 'label' => 'Submitted'],
                        'late_pending' => ['bg' => 'var(--amber-bg)', 'color' => 'var(--amber)', 'label' => 'Late – Pending'],
                        'accepted' => ['bg' => 'var(--green-bg)', 'color' => 'var(--green)', 'label' => 'Accepted'],
                        'rejected' => ['bg' => 'var(--red-bg)', 'color' => 'var(--red)', 'label' => 'Rejected'],
                    ];

                    $stepMap = [
                        'done' => ['bg' => 'var(--green-bg)', 'color' => 'var(--green)', 'dotBg' => 'var(--green-bg)', 'dotColor' => 'var(--green)', 'symbol' => '✓'],
                        'pending' => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)', 'dotBg' => 'var(--blue-bg)', 'dotColor' => 'var(--blue)', 'symbol' => '●'],
                        'rejected' => ['bg' => 'var(--red-bg)', 'color' => 'var(--red)', 'dotBg' => 'var(--red-bg)', 'dotColor' => 'var(--red)', 'symbol' => '✕'],
                        'waiting' => ['bg' => 'var(--gray-bg)', 'color' => 'var(--text3)', 'dotBg' => 'var(--gray-bg)', 'dotColor' => 'var(--text3)', 'symbol' => '○'],
                    ];

                    $status = $statusMap[$request['status']] ?? ['bg' => 'var(--gray-bg)', 'color' => 'var(--gray)', 'label' => ucfirst(str_replace('_', ' ', $request['status']))];
                @endphp

                <div class="rounded-[12px] bg-apis-bg p-[14px_18px] mb-2.5"
                     style="border: 0.5px solid var(--border)">
                    <div class="flex gap-[7px] items-center mb-1 flex-wrap">
                        <span class="font-mono text-[11px] text-apis-text2">{{ $request['id'] }}</span>
                        <span class="apis-badge" style="background: {{ $status['bg'] }}; color: {{ $status['color'] }};">
                            {{ $status['label'] }}
                        </span>
                        @if ($request['isLate'])
                            <span class="text-[10px] px-[6px] py-[1px] rounded-[3px] font-medium"
                                  style="background: var(--amber-bg); color: var(--amber)">
                                LATE
                            </span>
                        @endif
                    </div>

                    <p class="text-[13px] font-medium m-0 mb-[2px] text-apis-text">{{ $request['title'] }}</p>
                    <p class="text-[11px] text-apis-text2 m-0 mb-[10px]">
                        Needed: {{ $request['needed'] }} · Submitted: {{ $request['submitted'] }}
                    </p>

                    <p class="text-[10px] text-apis-text2 mb-[7px] font-medium uppercase tracking-[0.07em]">Status chain</p>
                    <div class="flex items-center gap-1 flex-wrap">
                        @foreach ($request['chain'] as $index => $step)
                            @php
                                $stepStyle = $stepMap[$step['st']] ?? $stepMap['waiting'];
                            @endphp

                            <div class="flex items-center gap-1">
                                @if ($index > 0)
                                    <span class="text-[10px] text-apis-text3 mr-[2px]">›</span>
                                @endif

                                <div class="apis-step-pill" style="background: {{ $stepStyle['bg'] }};">
                                    <span class="apis-step-dot"
                                          style="background: {{ $stepStyle['dotBg'] }}; color: {{ $stepStyle['dotColor'] }};">
                                        {{ $stepStyle['symbol'] }}
                                    </span>
                                    <span class="text-[10px] whitespace-nowrap"
                                          style="color: {{ $stepStyle['color'] }}; font-weight: {{ $step['st'] === 'pending' ? '500' : '400' }};">
                                        {{ $step['role'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
