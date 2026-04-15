@extends('layouts.app')

@section('title', 'Late Filings | EngiStart')
@section('header', 'Late Filings')
@section('subheader', 'Validate late-submitted requests and decide whether they may proceed.')

@section('sidebar')
    <a href="{{ route('dh-gen-services.late-filings') }}" class="flex items-center justify-between rounded-md px-3 py-2 text-sm font-medium bg-apis-bg text-apis-text" style="border: 0.5px solid var(--border2)">
        <span>Late Filings</span>
        <span class="text-[10px] px-1.5 py-0.5 rounded" style="background: var(--amber-bg); color: var(--amber)">{{ $this->filteredItems->count() }}</span>
    </a>
    <a href="{{ route('dh-gen-services.noting') }}" class="flex items-center justify-between rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text">
        <span>For Noting</span>
        <span class="text-[10px] px-1.5 py-0.5 rounded" style="background: var(--blue-bg); color: var(--blue)">3</span>
    </a>
    <a href="{{ route('dh-gen-services.change-request') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text">Settings Change Request</a>
@endsection

@section('sidebarFooter')
    <p class="mb-1 text-[10px] text-apis-text3">Signed in as</p>
    <p class="text-xs font-medium leading-tight text-apis-text">Ancel Roque</p>
    <p class="mt-0.5 text-[11px] text-apis-blue">DH Gen Services</p>
@endsection

@section('content')
<div class="p-6 overflow-y-auto h-full">
    <div class="w-full">
        <style>
            .apis-card { border: 0.5px solid var(--border); border-radius: 12px; background: var(--bg); overflow: hidden; margin-bottom: 10px; }
            .apis-card-button { border-radius: 8px; font-size: 12px; padding: 7px 16px; transition: background 0.12s, border-color 0.12s, color 0.12s; }
            .apis-step-dot { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 9999px; font-size: 9px; font-weight: 600; flex-shrink: 0; }
            .apis-remarks-control, .apis-toolbar-control { width: 100%; border-radius: 8px; padding: 10px 12px; font-size: 12px; line-height: 1.5; color: var(--text); background: var(--bg); outline: none; border: 0.5px solid var(--border2); transition: border-color 0.15s, box-shadow 0.15s; box-shadow: none; -webkit-appearance: none; appearance: none; }
            .apis-toolbar-control { min-height: 34px; padding-top: 0; padding-bottom: 0; }
            .apis-remarks-control { min-height: 88px; resize: vertical; }
            .apis-remarks-control:focus, .apis-toolbar-control:focus { border-color: #378add; box-shadow: 0 0 0 3px rgba(55, 138, 221, 0.1); }
        </style>

        @if ($actionMessage)
            @include('partials.apis.alert', ['type' => $actionTone, 'message' => $actionMessage])
        @endif

        @if ($this->filteredItems->isNotEmpty())
            @include('partials.apis.alert', ['type' => 'warn', 'message' => $this->filteredItems->count() . ' late filing' . ($this->filteredItems->count() !== 1 ? 's' : '') . ' pending your validation.'])
        @endif

        <div class="rounded-[12px] p-[12px_14px] mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between" style="border: 0.5px solid var(--border); background: var(--bg2)">
            <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1.5fr)_180px_180px] gap-3 flex-1 w-full">
                <div><label class="block text-[10px] text-apis-text2 mb-1 font-medium uppercase tracking-[0.07em]">Search</label><input type="text" wire:model.live.debounce.300ms="search" class="apis-toolbar-control" placeholder="Search by request ID, title, farm, or requester..."></div>
                <div><label class="block text-[10px] text-apis-text2 mb-1 font-medium uppercase tracking-[0.07em]">Type</label><select wire:model.live="typeFilter" class="apis-toolbar-control"><option value="all">All types</option>@foreach ($this->typeOptions as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select></div>
                <div><label class="block text-[10px] text-apis-text2 mb-1 font-medium uppercase tracking-[0.07em]">Sort</label><select wire:model.live="sortBy" class="apis-toolbar-control"><option value="latest">Latest submitted</option><option value="needed_asc">Date needed: earliest</option><option value="needed_desc">Date needed: latest</option></select></div>
            </div>
            <div class="flex items-end gap-3 lg:justify-end"><div><label class="block text-[10px] text-apis-text2 mb-1 font-medium uppercase tracking-[0.07em]">Per page</label><select wire:model.live="perPage" class="apis-toolbar-control w-[92px]"><option value="5">5</option><option value="10">10</option><option value="15">15</option></select></div></div>
        </div>

        @forelse ($this->paginatedItems as $request)
            <div class="apis-card" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="w-full text-left p-[14px_18px] flex justify-between items-start gap-3" style="cursor: pointer;">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-[7px] mb-[5px] flex-wrap">
                            <span class="font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['id'] }}</span>
                            <span class="text-[11px] px-2 py-0.5 rounded font-medium" style="background: var(--amber-bg); color: var(--amber)">Late Pending</span>
                        </div>
                        <p class="text-[14px] font-medium m-0 mb-[3px] overflow-hidden text-ellipsis whitespace-nowrap text-apis-text">{{ $request['title'] }}</p>
                        <p class="text-[11px] text-apis-text2 m-0">{{ $request['farm'] }} · Needed {{ $request['needed'] }} · {{ $request['days'] }}d ahead · By {{ $request['by'] }}</p>
                    </div>
                    <span class="text-[10px] text-apis-text3 flex-shrink-0 mt-[3px]"><span x-show="!open">▼</span><span x-show="open">▲</span></span>
                </button>
                <div x-cloak x-show="open" class="border-t p-[16px_18px]" style="border-color: var(--border)">
                    <div class="mb-[14px] space-y-[8px] text-[12px] text-apis-text2">
                        <div><span class="mr-1">Type:</span><span class="text-apis-text">{{ $request['type'] }}</span></div>
                        <div><span class="mr-1">Purpose:</span><span class="text-apis-text">{{ $request['purpose'] }}</span></div>
                        <div><span class="mr-1">Justification Letter:</span><span class="text-apis-blue">{{ $request['jl'] }}</span></div>
                    </div>
                    <p class="text-[12px] leading-[1.7] text-apis-text mb-[14px] border-l-2 pl-3" style="border-color: var(--border)">{{ $request['desc'] }}</p>
                    <div class="mb-[14px]"><p class="text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Routing Chain</p><div class="flex flex-col gap-[7px]">@foreach ($request['chain'] as $step) @php $stepStyle = match ($step['st']) { 'done' => ['bg' => 'var(--green-bg)', 'color' => 'var(--green)', 'symbol' => '✓'], 'pending' => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)', 'symbol' => '●'], 'rejected' => ['bg' => 'var(--red-bg)', 'color' => 'var(--red)', 'symbol' => '✕'], default => ['bg' => 'var(--gray-bg)', 'color' => 'var(--text3)', 'symbol' => '○'], }; @endphp <div class="flex items-start gap-[9px]"><span class="apis-step-dot" style="background: {{ $stepStyle['bg'] }}; color: {{ $stepStyle['color'] }};">{{ $stepStyle['symbol'] }}</span><div class="flex-1 pt-[1px]"><div class="flex justify-between items-baseline gap-2"><span class="text-[12px] {{ $step['st'] === 'waiting' ? 'text-apis-text3' : 'text-apis-text' }} {{ $step['st'] === 'pending' ? 'font-medium' : 'font-normal' }}">{{ $step['role'] }} — {{ $step['action'] }}</span>@if ($step['date'])<span class="text-[11px] text-apis-text3 flex-shrink-0">{{ $step['date'] }}</span>@endif</div>@if ($step['user'])<span class="text-[11px] text-apis-text2">{{ $step['user'] }}</span>@endif</div></div> @endforeach</div></div>
                    <div class="mb-[14px]"><label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Remarks</label><textarea wire:model.live="remarks.{{ $request['id'] }}" class="apis-remarks-control" placeholder="Add validation remarks here..."></textarea></div>
                    <div class="flex gap-2 flex-wrap"><button type="button" wire:click="approve(@js($request['id']))" class="apis-card-button font-medium" style="background: var(--amber-bg); color: var(--amber); border: 0.5px solid var(--amber-bd)">Approve Late Request</button><button type="button" wire:click="reject(@js($request['id']))" class="apis-card-button font-medium" style="background: var(--red-bg); color: var(--red); border: 0.5px solid var(--red-bd)">Reject</button></div>
                </div>
            </div>
        @empty
            <div class="text-center py-[80px] text-[13px] text-apis-text2">No late filings match the current filters.</div>
        @endforelse

        <div class="mt-4 rounded-[12px] p-[12px_14px] flex flex-col gap-3 md:flex-row md:items-center md:justify-between" style="border: 0.5px solid var(--border); background: var(--bg)">
            <p class="text-[12px] text-apis-text2 m-0">Showing {{ $this->showingFrom }}-{{ $this->showingTo }} of {{ $this->filteredItems->count() }} request{{ $this->filteredItems->count() !== 1 ? 's' : '' }}</p>
            <div class="flex items-center gap-2"><button type="button" wire:click="previousPage" @disabled($page <= 1) class="apis-card-button" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text); opacity: {{ $page <= 1 ? '0.5' : '1' }};">Previous</button><span class="text-[12px] text-apis-text2 px-1">Page {{ $page }} of {{ $this->totalPages }}</span><button type="button" wire:click="nextPage" @disabled($page >= $this->totalPages) class="apis-card-button" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text); opacity: {{ $page >= $this->totalPages ? '0.5' : '1' }};">Next</button></div>
        </div>
    </div>
</div>
@endsection
