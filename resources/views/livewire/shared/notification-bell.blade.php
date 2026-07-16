<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        @click="open = !open"
        class="relative rounded px-2 py-1 text-apis-text2 hover:bg-apis-bg2 transition-colors"
        style="border: 0.5px solid var(--border2)"
        aria-label="Notifications"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full text-[10px] font-bold text-white"
                  style="background: var(--red)">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        class="absolute right-0 top-9 z-[95] w-[min(22rem,calc(100vw-2rem))] rounded-[12px] overflow-hidden"
        style="background: var(--bg); border: 0.5px solid var(--border2); box-shadow: 0 10px 30px rgba(0,0,0,0.15);"
    >
        <div class="flex items-center justify-between px-4 py-3" style="border-bottom: 0.5px solid var(--border)">
            <span class="text-[13px] font-semibold text-apis-text">Notifications</span>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllRead" class="text-[11px] font-medium" style="color: var(--blue)">
                    Mark all read
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification['data'];
                    $icon = match ($data['event'] ?? null) {
                        'submitted', 'jl_submitted' => '📋',
                        'recommended', 'jl_recommended' => '➡️',
                        'assessment_meeting_needed' => '📅',
                        'accepted' => '✅',
                        'noted' => '🛠️',
                        'initialized' => '🎉',
                        'returned_to_requestor' => '↩️',
                        default => '🔔',
                    };
                    $createdAt = \Illuminate\Support\Carbon::parse($notification['created_at']);
                @endphp
                <div
                    wire:click="markRead('{{ $notification['id'] }}')"
                    class="flex gap-3 px-4 py-3 cursor-pointer transition-colors hover:bg-apis-bg2"
                    style="{{ ! $notification['read_at'] ? 'background: var(--blue-bg);' : '' }} border-bottom: 0.5px solid var(--border)"
                >
                    <span class="mt-0.5 text-[15px] leading-none">{{ $icon }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[12px] {{ ! $notification['read_at'] ? 'font-semibold' : 'font-medium' }} text-apis-text m-0">
                            {{ $data['title'] ?? 'Notification' }}
                        </p>
                        <p class="text-[11px] text-apis-text2 mt-0.5 mb-0 truncate">{{ $data['body'] ?? '' }}</p>
                        <p class="text-[10px] text-apis-text3 mt-1 mb-0">{{ $createdAt->diffForHumans() }}</p>
                    </div>
                    @if (! $notification['read_at'])
                        <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full" style="background: var(--blue)"></span>
                    @endif
                </div>
            @empty
                <p class="px-4 py-6 text-center text-[12px] text-apis-text2">No notifications yet</p>
            @endforelse
        </div>
    </div>
</div>
