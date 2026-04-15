@extends('layouts.app')

@section('title', 'All Requests | EngiStart')
@section('header', 'All Requests')
@section('subheader', 'Monitor request flow across the entire system.')

@section('sidebar')
    <a href="{{ route('it-admin.all-requests') }}" class="flex items-center rounded-md px-3 py-2 text-sm font-medium bg-apis-bg text-apis-text" style="border: 0.5px solid var(--border2)">All Requests</a>
    <a href="{{ route('it-admin.users') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text">User Management</a>
    <a href="{{ route('it-admin.audit') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text">Audit Trail</a>
    <a href="{{ route('it-admin.override') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text">Status Override</a>
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
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-5">
        <div class="rounded-[12px] p-[14px_16px]" style="background: var(--bg2); border: 0.5px solid var(--border)"><p class="text-[11px] text-apis-text2 m-0 mb-1">Total</p><p class="text-[26px] font-medium m-0 text-apis-text">1</p></div>
        <div class="rounded-[12px] p-[14px_16px]" style="background: var(--bg2); border: 0.5px solid var(--border)"><p class="text-[11px] text-apis-text2 m-0 mb-1">In Progress</p><p class="text-[26px] font-medium m-0 text-apis-text">1</p></div>
        <div class="rounded-[12px] p-[14px_16px]" style="background: var(--bg2); border: 0.5px solid var(--border)"><p class="text-[11px] text-apis-text2 m-0 mb-1">Completed</p><p class="text-[26px] font-medium m-0 text-apis-text">0</p></div>
        <div class="rounded-[12px] p-[14px_16px]" style="background: var(--bg2); border: 0.5px solid var(--border)"><p class="text-[11px] text-apis-text2 m-0 mb-1">Late Filings</p><p class="text-[26px] font-medium m-0 text-apis-text">0</p></div>
    </div>

    <div class="rounded-[12px] p-[12px_14px] mb-4 flex gap-2 flex-wrap items-center" style="border: 0.5px solid var(--border); background: var(--bg)">
        <input type="text" class="w-[220px] rounded-[8px] px-3 h-[34px] text-[12px]" style="border: 0.5px solid var(--border2); background: var(--bg); color: var(--text)" placeholder="Search by ID or title...">
        <button type="button" class="text-[11px] px-3 py-1 rounded-[6px] font-medium" style="background: var(--bg2); color: var(--text)">All</button>
        <button type="button" class="text-[11px] px-3 py-1 rounded-[6px]" style="background: transparent; color: var(--text2)">Submitted</button>
        <button type="button" class="text-[11px] px-3 py-1 rounded-[6px]" style="background: transparent; color: var(--text2)">Recommended</button>
        <button type="button" class="text-[11px] px-3 py-1 rounded-[6px]" style="background: transparent; color: var(--text2)">VP Approved</button>
    </div>

    <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-[12px]">
                <thead>
                    <tr style="background: var(--bg2)">
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">ID</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Project Title</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Farm</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">By</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Date Needed</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Days</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Routing</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->requests as $request)
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td class="px-[14px] py-[9px] font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['id'] }}</td>
                            <td class="px-[14px] py-[9px] font-medium text-apis-text whitespace-nowrap">{{ $request['title'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['farm'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $request['by'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text2 whitespace-nowrap">{{ $request['needed'] }}</td>
                            <td class="px-[14px] py-[9px]"><span class="font-medium" style="color: var(--green)">{{ $request['days'] }}d</span></td>
                            <td class="px-[14px] py-[9px]"><span class="text-[10px] px-2 py-0.5 rounded" style="background: var(--blue-bg); color: var(--blue)">{{ $request['routing'] }}</span></td>
                            <td class="px-[14px] py-[9px]"><span class="text-[10px] px-2 py-0.5 rounded font-medium" style="background: var(--blue-bg); color: var(--blue)">Submitted</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
