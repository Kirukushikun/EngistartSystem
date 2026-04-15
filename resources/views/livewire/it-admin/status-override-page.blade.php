@extends('layouts.app')

@section('title', 'Status Override | EngiStart')
@section('header', 'Status Override')
@section('subheader', 'Apply exceptional workflow changes with proper authorization.')

@section('sidebar')
    <a href="{{ route('it-admin.all-requests') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text">All Requests</a>
    <a href="{{ route('it-admin.users') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text">User Management</a>
    <a href="{{ route('it-admin.audit') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text">Audit Trail</a>
    <a href="{{ route('it-admin.override') }}" class="flex items-center rounded-md px-3 py-2 text-sm font-medium bg-apis-bg text-apis-text" style="border: 0.5px solid var(--border2)">Status Override</a>
    <a href="{{ route('it-admin.pending-changes') }}" class="flex items-center justify-between rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text"><span>Pending Changes</span><span class="text-[10px] px-1.5 py-0.5 rounded" style="background: var(--blue-bg); color: var(--blue)">1</span></a>
    <a href="{{ route('it-admin.settings') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text">Settings</a>
@endsection

@section('sidebarFooter')
    <p class="mb-1 text-[10px] text-apis-text3">Signed in as</p>
    <p class="text-xs font-medium leading-tight text-apis-text">Jeff Montiano</p>
    <p class="mt-0.5 text-[11px] text-apis-blue">IT Admin</p>
@endsection

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
