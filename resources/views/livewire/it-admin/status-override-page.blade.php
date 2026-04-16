@extends('layouts.app')

@section('title', 'Status Override | EngiStart')
@section('header', 'Status Override')
@section('subheader', 'Apply exceptional workflow changes with proper authorization.')

@section('content')
<div class="p-6 overflow-y-auto h-full">
    @include('partials.apis.alert', ['type' => 'warn', 'message' => 'Status overrides are logged in the audit trail. Use only in exceptional circumstances and ensure proper authorization.'])

    @if ($message)
        @include('partials.apis.alert', ['type' => 'info', 'message' => $message])
    @endif

    @foreach ($this->requests as $request)
        <div class="rounded-[12px] p-[14px_18px] mb-3 flex justify-between items-center flex-wrap gap-3" style="border: 0.5px solid var(--border); background: var(--bg)">
            <div>
                <div class="flex gap-2 mb-1 items-center">
                    <span class="font-mono text-[11px] text-apis-text2">{{ $request['id'] }}</span>
                    <span class="text-[10px] px-2 py-0.5 rounded font-medium" style="background: var(--blue-bg); color: var(--blue)">Recommended</span>
                </div>
                <p class="text-[13px] font-medium m-0 mb-0.5 text-apis-text">{{ $request['title'] }}</p>
                <p class="text-[11px] m-0 text-apis-text2">{{ $request['farm'] }}</p>
            </div>
            <div class="flex gap-2 items-center">
                <select wire:model.live="overrideStatus.{{ $request['id'] }}" class="w-[160px] h-[34px] rounded-[8px] px-3 text-[12px]" style="border: 0.5px solid var(--border2); background: var(--bg); color: var(--text)">
                    <option value="submitted">submitted</option>
                    <option value="recommended">recommended</option>
                    <option value="vp_approved">vp approved</option>
                    <option value="noted">noted</option>
                    <option value="accepted">accepted</option>
                    <option value="rejected">rejected</option>
                </select>
                <button type="button" wire:click="applyOverride(@js($request['id']))" class="text-[11px] px-4 py-2 rounded-[8px] font-medium" style="background: var(--amber-bg); color: var(--amber); border: 0.5px solid var(--amber-bd)">Override</button>
            </div>
        </div>
    @endforeach
</div>
@endsection
