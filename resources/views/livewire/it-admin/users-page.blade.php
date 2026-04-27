<div class="p-6 overflow-y-auto h-full space-y-4">
    <div class="rounded-[12px] p-[14px]" style="border: 0.5px solid var(--border); background: var(--bg2)">
        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.8fr)_180px_180px_180px_100px] gap-3 items-end">
            <div>
                <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" class="apis-toolbar-control w-full" placeholder="Search by name, email, role, farm, department, or ID...">
            </div>

            <div>
                <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Status</label>
                <select wire:model.live="statusFilter" class="apis-toolbar-control w-full">
                    <option value="all">All statuses</option>
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                    <option value="no access">No access</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Role</label>
                <select wire:model.live="roleFilter" class="apis-toolbar-control w-full">
                    <option value="all">All roles</option>
                    @foreach ($this->roleOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Sort</label>
                <select wire:model.live="sortBy" class="apis-toolbar-control w-full">
                    <option value="name_asc">Name A-Z</option>
                    <option value="name_desc">Name Z-A</option>
                    <option value="email_asc">Email A-Z</option>
                    <option value="email_desc">Email Z-A</option>
                    <option value="status">Status</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Per page</label>
                <select wire:model.live="perPage" class="apis-toolbar-control w-full">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
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
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Department</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Status</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->paginatedUsers as $user)
                        <tr style="border-top: 0.5px solid var(--border)">

                            {{-- Name --}}
                            <td class="px-[14px] py-[9px] font-medium text-apis-text whitespace-nowrap">
                                {{ $user['name'] }}
                            </td>

                            {{-- Email --}}
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">
                                {{ $user['email'] }}
                            </td>

                            {{-- Role --}}
                            <td class="px-[14px] py-[9px]">
                                <span class="text-[11px] px-2 py-0.5 rounded"
                                    style="background: var(--blue-bg); color: var(--blue)">
                                    {{ $user['role'] }}
                                </span>
                            </td>

                            {{-- Farm --}}
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">
                                {{ $user['farm'] }}
                            </td>

                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">
                                {{ $user['department'] }}
                            </td>

                            {{-- Status --}}
                            <td class="px-[14px] py-[9px]">
                                @php
                                    $statusStyles = match($user['status']) {
                                        'active'    => ['background: var(--green-bg); color: var(--green)', 'Active'],
                                        'disabled'  => ['background: var(--red-bg); color: var(--red)', 'Disabled'],
                                        default     => ['background: var(--bg2); color: var(--text2)', 'No Access'],
                                    };
                                @endphp
                                <span class="text-[10px] px-2 py-0.5 rounded font-medium"
                                    style="{{ $statusStyles[0] }}">
                                    {{ $statusStyles[1] }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-[14px] py-[9px]">
                                <div class="flex gap-2 flex-wrap">
                                    @if ($user['status'] === 'no access')
                                        <button type="button" wire:click="grantAccess({{ $user['id'] }})"
                                            class="text-[11px] px-2 py-1 rounded-[6px] font-medium"
                                            style="background: var(--green-bg); color: var(--green); border: 0.5px solid var(--green-bd)">
                                            Grant Access
                                        </button>
                                    @else
                                        <button type="button" wire:click="editUser({{ $user['id'] }})"
                                            class="text-[11px] px-2 py-1 rounded-[6px]"
                                            style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text)">
                                            Edit
                                        </button>
                                        <button type="button" wire:click="editRole({{ $user['id'] }})"
                                            class="text-[11px] px-2 py-1 rounded-[6px]"
                                            style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text)">
                                            Role
                                        </button>
                                        <button type="button" wire:click="toggleAccess({{ $user['id'] }})"
                                            class="text-[11px] px-2 py-1 rounded-[6px] font-medium"
                                            style="background: {{ $user['status'] === 'active' ? 'var(--red-bg)' : 'var(--green-bg)' }}; color: {{ $user['status'] === 'active' ? 'var(--red)' : 'var(--green)' }}; border: 0.5px solid {{ $user['status'] === 'active' ? 'var(--red-bd)' : 'var(--green-bd)' }};">
                                            {{ $user['status'] === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    @endif
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-[14px] py-8 text-center text-[12px] text-apis-text2">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3 flex-wrap rounded-[12px] p-[12px_14px]" style="border: 0.5px solid var(--border); background: var(--bg)">
        <p class="text-[12px] text-apis-text2 m-0">
            Showing {{ $this->showingFrom }}-{{ $this->showingTo }} of {{ $this->filteredUsers->count() }} user{{ $this->filteredUsers->count() === 1 ? '' : 's' }}
        </p>

        <div class="flex items-center gap-2">
            <button type="button" wire:click="previousPage" @disabled($page <= 1)
                class="text-[11px] px-3 py-1.5 rounded-[8px]"
                style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text); opacity: {{ $page <= 1 ? '0.5' : '1' }};">
                Previous
            </button>

            <span class="text-[12px] text-apis-text2 px-1">Page {{ $page }} of {{ $this->totalPages }}</span>

            <button type="button" wire:click="nextPage" @disabled($page >= $this->totalPages)
                class="text-[11px] px-3 py-1.5 rounded-[8px]"
                style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text); opacity: {{ $page >= $this->totalPages ? '0.5' : '1' }};">
                Next
            </button>
        </div>
    </div>

    @if ($this->isModalOpen)
        <div class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <button type="button" wire:click="cancelForm" class="absolute inset-0 bg-black/40"></button>

            <div class="relative w-full max-w-2xl rounded-[14px] bg-apis-bg shadow-xl" style="border: 0.5px solid var(--border2)">
                <div class="p-[18px_20px] border-b" style="border-color: var(--border)">
                    <div class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.08em]"
                        style="background: {{ $formMode === 'grant' ? 'var(--green-bg)' : 'var(--blue-bg)' }}; color: {{ $formMode === 'grant' ? 'var(--green)' : 'var(--blue)' }}; border: 0.5px solid {{ $formMode === 'grant' ? 'var(--green-bd)' : 'var(--blue-bd)' }};">
                        {{ $formMode === 'grant' ? 'Grant Access' : ($formMode === 'role' ? 'Update Role' : 'Edit Access') }}
                    </div>
                    <h3 class="mt-3 text-[16px] font-semibold text-apis-text">{{ $selectedUserName }}</h3>
                    <p class="mt-1 text-[13px] leading-[1.6] text-apis-text2">{{ $selectedUserEmail }}</p>
                </div>

                <div class="p-[18px_20px] space-y-4">
                    <div class="grid grid-cols-1 {{ $formMode === 'role' ? '' : 'md:grid-cols-3' }} gap-4">
                        <div>
                            <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Role</label>
                            <select wire:model="form.role" class="apis-toolbar-control w-full">
                                @foreach ($this->roleOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('form.role')
                                <p class="mt-2 text-[11px]" style="color: var(--red)">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($formMode !== 'role')
                            <div>
                                <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Farm</label>
                                <input type="text" wire:model="form.farm" class="apis-toolbar-control w-full" placeholder="Assign farm if applicable">
                                @error('form.farm')
                                    <p class="mt-2 text-[11px]" style="color: var(--red)">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Department</label>
                                <input type="text" wire:model="form.department" class="apis-toolbar-control w-full" placeholder="Assign department if applicable">
                                @error('form.department')
                                    <p class="mt-2 text-[11px]" style="color: var(--red)">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end gap-2 p-[16px_20px] border-t" style="border-color: var(--border)">
                    <button type="button" wire:click="cancelForm" class="apis-card-button">Cancel</button>
                    <button type="button" wire:click="saveUser" class="apis-card-button font-medium"
                        style="background: {{ $formMode === 'grant' ? 'var(--green-bg)' : 'var(--blue-bg)' }}; color: {{ $formMode === 'grant' ? 'var(--green)' : 'var(--blue)' }}; border: 0.5px solid {{ $formMode === 'grant' ? 'var(--green-bd)' : 'var(--blue-bd)' }};">
                        {{ $formMode === 'grant' ? 'Create Access' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>