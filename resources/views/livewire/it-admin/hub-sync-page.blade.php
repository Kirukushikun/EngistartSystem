<div class="p-6 overflow-y-auto h-full space-y-4">

    {{-- Header row --}}
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <a href="{{ route('it-admin.users') }}" class="text-[12px] text-apis-text2 hover:text-apis-text">&larr; Back to User Management</a>

        @if ($hasConnection)
            <div class="flex items-center gap-3">
                @if ($lastSyncedAt)
                    <span class="text-[11px] font-medium" style="color: {{ $lastSyncedStale ? '#b45309' : 'var(--text2)' }}">
                        Last synced {{ $lastSyncedAt }}
                    </span>
                @else
                    <span class="text-[11px] text-apis-text2">Never synced</span>
                @endif

                <button type="button" wire:click="refreshPreview"
                    class="text-[11px] px-3 py-1.5 rounded-[8px]"
                    style="border: 0.5px solid var(--border2); background: var(--bg2); color: var(--text);">
                    Refresh preview
                </button>

                <button type="button" wire:click="resetConnection"
                    wire:confirm="Reset the Access Hub connection? You'll need to enroll again with a new code before syncing."
                    class="text-[11px] px-3 py-1.5 rounded-[8px]"
                    style="border: 0.5px solid var(--red-bd); background: var(--red-bg); color: var(--red);">
                    Reset connection
                </button>
            </div>
        @endif
    </div>

    @unless ($hasConnection)

        {{-- ENROLLMENT --}}
        <div class="rounded-[12px] p-6 space-y-4 max-w-xl" style="border: 0.5px solid var(--border); background: var(--bg)">
            <div>
                <p class="text-[13px] font-medium text-apis-text m-0">Connect to the Access Hub</p>
                <p class="text-[12px] text-apis-text2 mt-1 m-0">
                    Enter the one-time enrollment code from the hub admin. This system exchanges it for a
                    connection that's stored encrypted here and never appears again once saved.
                </p>
            </div>

            @if ($enrollmentError)
                <div class="rounded-[10px] px-4 py-3 text-[12px]" style="background: var(--red-bg); color: var(--red); border: 0.5px solid var(--red-bd)">
                    {{ $enrollmentError }}
                </div>
            @endif

            <div>
                <label class="block text-[10px] text-apis-text2 mb-2 font-medium uppercase tracking-[0.07em]">Enrollment code</label>
                <input type="text" wire:model="enrollmentCode" class="apis-toolbar-control w-full" placeholder="HUB-XXXX-XXXX" autofocus>
                @error('enrollmentCode')
                    <p class="mt-2 text-[11px]" style="color: var(--red)">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="button" wire:click="enroll" class="apis-card-button font-medium"
                    style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd);">
                    Connect
                </button>
            </div>
        </div>

    @else

        @if ($flash)
            <div class="rounded-[10px] px-4 py-3 text-[12px]"
                 style="background: var(--green-bg); color: var(--green); border: 0.5px solid var(--green-bd)">
                {{ $flash }}
            </div>
        @endif

        @if ($error)
            <div class="rounded-[12px] p-6 text-center space-y-2"
                 style="border: 0.5px solid var(--red-bd); background: var(--red-bg)">
                <p class="text-[13px] font-medium m-0" style="color: var(--red)">{{ $error }}</p>

                @if ($credentialsRejected)
                    <button type="button" wire:click="resetConnection" class="text-[11px] px-3 py-1.5 rounded-[8px] mt-1"
                        style="border: 0.5px solid var(--red-bd); background: var(--bg); color: var(--red);">
                        Reset connection
                    </button>
                @else
                    <p class="text-[12px] m-0" style="color: var(--red)">Nothing was changed. The panel still works with the hub offline.</p>
                @endif
            </div>
        @else

            {{-- NEW --}}
            <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
                <div class="px-[14px] py-[10px] flex items-center justify-between" style="background: var(--bg2)">
                    <span class="text-[12px] font-medium text-apis-text">New &mdash; on the hub, not here yet</span>
                    <span class="text-[11px] text-apis-text2">{{ count($newPeople) }}</span>
                </div>

                @forelse ($newPeople as $row)
                    <label class="flex items-start gap-3 px-[14px] py-[10px]" style="border-top: 0.5px solid var(--border)">
                        <input type="checkbox" wire:model="selectedNew.{{ $row['id'] }}" class="mt-1">
                        <div class="min-w-0 flex-1">
                            <div class="text-[12px] font-medium text-apis-text">{{ $row['name'] }}</div>
                            <div class="text-[11px] text-apis-text2">{{ $row['email'] }}</div>
                            <div class="text-[11px] text-apis-text2 mt-1">
                                Grants: <span class="text-apis-text">{{ $this->roleLabel($row['role']) }}</span>
                                &nbsp;&middot;&nbsp; hub roles: {{ implode(', ', $row['hub_roles']) }}
                            </div>
                            <div class="text-[11px] text-apis-text2">
                                {{ $row['farm'] ?? '—' }} &middot; {{ $row['department'] ?? '—' }} &middot; {{ $row['position'] ?? '—' }}
                            </div>
                        </div>
                    </label>
                @empty
                    <div class="px-[14px] py-6 text-center text-[12px] text-apis-text2">Nothing new.</div>
                @endforelse
            </div>

            {{-- CHANGED --}}
            <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
                <div class="px-[14px] py-[10px] flex items-center justify-between" style="background: var(--bg2)">
                    <span class="text-[12px] font-medium text-apis-text">Changed &mdash; hub-managed, access would differ</span>
                    <span class="text-[11px] text-apis-text2">{{ count($changedPeople) }}</span>
                </div>

                @forelse ($changedPeople as $row)
                    <label class="flex items-start gap-3 px-[14px] py-[10px]" style="border-top: 0.5px solid var(--border)">
                        <input type="checkbox" wire:model="selectedChanged.{{ $row['id'] }}" class="mt-1">
                        <div class="min-w-0 flex-1">
                            <div class="text-[12px] font-medium text-apis-text">{{ $row['name'] }}</div>
                            <div class="text-[11px] text-apis-text2">{{ $row['email'] }}</div>

                            @if ($row['revoke'] ?? false)
                                <div class="text-[11px] mt-1" style="color: var(--red)">
                                    Access removed at the hub &mdash; would be disabled here.
                                </div>
                            @else
                                <div class="text-[11px] text-apis-text2 mt-1">
                                    <span class="line-through">{{ $this->roleLabel($row['current']['role']) }},
                                        {{ $row['current']['farm'] ?? '—' }} / {{ $row['current']['department'] ?? '—' }} / {{ $row['current']['position'] ?? '—' }}</span>
                                </div>
                                <div class="text-[11px] text-apis-text mt-0.5">
                                    &rarr; {{ $this->roleLabel($row['role']) }},
                                    {{ $row['farm'] ?? '—' }} / {{ $row['department'] ?? '—' }} / {{ $row['position'] ?? '—' }}
                                </div>
                            @endif
                        </div>
                    </label>
                @empty
                    <div class="px-[14px] py-6 text-center text-[12px] text-apis-text2">Nothing changed.</div>
                @endforelse
            </div>

            {{-- LOCAL ONLY --}}
            <div class="rounded-[12px] overflow-hidden" style="border: 0.5px solid var(--border); background: var(--bg)">
                <div class="px-[14px] py-[10px] flex items-center justify-between" style="background: var(--bg2)">
                    <span class="text-[12px] font-medium text-apis-text">Local only &mdash; the hub doesn't know them</span>
                    <span class="text-[11px] text-apis-text2">{{ count($localOnlyPeople) }}</span>
                </div>

                <div class="px-[14px] py-[8px] text-[11px] text-apis-text2" style="border-top: 0.5px solid var(--border)">
                    Shown for reassurance only. Sync never touches these.
                </div>

                @forelse ($localOnlyPeople as $row)
                    <div class="px-[14px] py-[8px] text-[12px]" style="border-top: 0.5px solid var(--border)">
                        <span class="text-apis-text">{{ $row['name'] }}</span>
                        <span class="text-apis-text2">
                            &middot; {{ $row['email'] }}
                            &middot; {{ $this->roleLabel($row['role']) }}
                            &middot; {{ $row['source'] }}@unless ($row['active']) &middot; disabled @endunless
                        </span>
                    </div>
                @empty
                    <div class="px-[14px] py-6 text-center text-[12px] text-apis-text2">None.</div>
                @endforelse
            </div>

            {{-- Apply --}}
            <div class="flex justify-end gap-2 rounded-[12px] p-[12px_14px]" style="border: 0.5px solid var(--border); background: var(--bg)">
                <button type="button" wire:click="apply"
                    @disabled(count($newPeople) === 0 && count($changedPeople) === 0)
                    class="apis-card-button font-medium"
                    style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd);">
                    Apply selected
                </button>
            </div>

        @endif
    @endunless
</div>
