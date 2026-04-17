<div class="p-6 overflow-y-auto h-full">
    <div class="flex justify-end mb-4">
        <button type="button" class="text-[12px] px-4 py-2 rounded-[8px] font-medium" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text)">+ Add User</button>
    </div>

    <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-[12px]">
                <thead>
                    <tr style="background: var(--bg2)">
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Name</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Email</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Role</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Farm</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Status</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->users as $user)
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td class="px-[14px] py-[9px] font-medium text-apis-text whitespace-nowrap">{{ $user['name'] }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $user['email'] }}</td>
                            <td class="px-[14px] py-[9px]"><span class="text-[11px] px-2 py-0.5 rounded" style="background: var(--blue-bg); color: var(--blue)">{{ $user['role'] }}</span></td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $user['farm'] }}</td>
                            <td class="px-[14px] py-[9px]"><span class="text-[10px] px-2 py-0.5 rounded font-medium" style="background: var(--green-bg); color: var(--green)">Active</span></td>
                            <td class="px-[14px] py-[9px]"><div class="flex gap-2"><button type="button" class="text-[11px] px-2 py-1 rounded-[6px]" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text)">Edit</button><button type="button" class="text-[11px] px-2 py-1 rounded-[6px]" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text)">Role</button><button type="button" class="text-[11px] px-2 py-1 rounded-[6px]" style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text)">Disable</button></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
