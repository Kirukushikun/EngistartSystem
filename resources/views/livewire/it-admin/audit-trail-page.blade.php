@extends('layouts.app')

@section('title', 'Audit Trail | EngiStart')
@section('header', 'Audit Trail')
@section('subheader', 'Track system activities and approval actions.')

@section('content')
<div class="p-6 overflow-y-auto h-full">
    <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
        <div class="px-[18px] py-[12px] flex justify-between items-center" style="border-bottom: 0.5px solid var(--border); background: var(--bg2)">
            <span class="text-[13px] font-medium text-apis-text">Activity Log</span>
            <button type="button" class="text-[11px] px-3 py-1 rounded-[6px]" style="border: 0.5px solid var(--border2); background: var(--bg); color: var(--text)">Export CSV</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-[12px]">
                <thead>
                    <tr style="background: var(--bg2)">
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Timestamp</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">User</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Role</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Action</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Request ID</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->logs as $log)
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td class="px-[14px] py-[9px] font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $log['ts'] }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text whitespace-nowrap">{{ $log['user'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $log['role'] }}</td>
                            <td class="px-[14px] py-[9px]"><span class="text-[11px] px-2 py-0.5 rounded font-medium" style="background: var(--blue-bg); color: var(--blue)">{{ $log['action'] }}</span></td>
                            <td class="px-[14px] py-[9px] font-mono text-[11px] text-apis-text2 whitespace-nowrap">{{ $log['id'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2">{{ $log['note'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
