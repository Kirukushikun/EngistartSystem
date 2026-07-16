<div class="p-6 overflow-y-auto h-full space-y-4">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <input type="text" wire:model.live.debounce.300ms="search" class="apis-toolbar-control w-full max-w-[320px]" placeholder="Search by name or email...">
        <button type="button" wire:click="createEngineer" class="apis-card-button font-medium" style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd)">
            + New Engineer Account
        </button>
    </div>

    <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-[12px]">
                <thead>
                    <tr style="background: var(--bg2)">
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Name</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Email</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Status</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Pending Initialization</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Initialized</th>
                        <th class="px-[14px] py-[9px] text-left text-[11px] font-medium text-apis-text2 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->engineers as $engineer)
                        <tr style="border-top: 0.5px solid var(--border)">
                            <td class="px-[14px] py-[9px] font-medium text-apis-text whitespace-nowrap">{{ $engineer->name }}</td>
                            <td class="px-[14px] py-[9px] text-[11px] text-apis-text2 whitespace-nowrap">{{ $engineer->email }}</td>
                            <td class="px-[14px] py-[9px]">
                                <span class="text-[10px] px-2 py-0.5 rounded font-medium"
                                      style="background: {{ $engineer->is_active ? 'var(--green-bg)' : 'var(--red-bg)' }}; color: {{ $engineer->is_active ? 'var(--green)' : 'var(--red)' }}">
                                    {{ $engineer->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="px-[14px] py-[9px] text-apis-text">{{ $engineer->pending_count }}</td>
                            <td class="px-[14px] py-[9px] text-apis-text">{{ $engineer->initialized_count }}</td>
                            <td class="px-[14px] py-[9px]">
                                <div class="flex gap-2 flex-wrap">
                                    <button type="button" wire:click="resetPassword({{ $engineer->id }})"
                                            class="text-[11px] px-2 py-1 rounded-[6px]"
                                            style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text)">
                                        Reset Password
                                    </button>
                                    <button type="button" wire:click="toggleActive({{ $engineer->id }})"
                                            class="text-[11px] px-2 py-1 rounded-[6px] font-medium"
                                            style="background: {{ $engineer->is_active ? 'var(--red-bg)' : 'var(--green-bg)' }}; color: {{ $engineer->is_active ? 'var(--red)' : 'var(--green)' }}; border: 0.5px solid {{ $engineer->is_active ? 'var(--red-bd)' : 'var(--green-bd)' }};">
                                        {{ $engineer->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-[14px] py-8 text-center text-[12px] text-apis-text2">No engineer accounts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($this->isModalOpen)
        <div class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <button type="button" wire:click="cancelForm" class="absolute inset-0 bg-black/40"></button>

            <div class="relative w-full max-w-lg rounded-[14px] bg-apis-bg shadow-xl" style="border: 0.5px solid var(--border2)">
                <div class="p-[18px_20px] border-b" style="border-color: var(--border)">
                    <h3 class="text-[16px] font-semibold text-apis-text m-0">
                        {{ $formMode === 'create' ? 'New Engineer Account' : 'Reset Engineer Password' }}
                    </h3>
                </div>

                <div class="p-[18px_20px] space-y-4">
                    @if ($formMode === 'create')
                        <div>
                            <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Name</label>
                            <input type="text" wire:model="form.name" class="apis-toolbar-control w-full">
                            @error('form.name') <p class="mt-2 text-[11px]" style="color: var(--red)">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Email (login)</label>
                            <input type="email" wire:model="form.email" class="apis-toolbar-control w-full">
                            @error('form.email') <p class="mt-2 text-[11px]" style="color: var(--red)">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <p class="text-[12px] text-apis-text2 m-0">Resetting password for <span class="font-medium text-apis-text">{{ $form['name'] }}</span> ({{ $form['email'] }})</p>
                    @endif

                    <div>
                        <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">{{ $formMode === 'create' ? 'Password' : 'New Password' }}</label>
                        <input type="password" wire:model="form.password" class="apis-toolbar-control w-full">
                        @error('form.password') <p class="mt-2 text-[11px]" style="color: var(--red)">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Confirm Password</label>
                        <input type="password" wire:model="form.password_confirmation" class="apis-toolbar-control w-full">
                    </div>
                </div>

                <div class="flex justify-end gap-2 p-[16px_20px] border-t" style="border-color: var(--border)">
                    <button type="button" wire:click="cancelForm" class="apis-card-button">Cancel</button>
                    <button type="button" wire:click="save" class="apis-card-button font-medium"
                            style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd)">
                        {{ $formMode === 'create' ? 'Create Account' : 'Reset Password' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
