<!DOCTYPE html>
<html lang="en"
    x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
    x-init="
        $watch('darkMode', value => {
            localStorage.setItem('darkMode', value);
            document.documentElement.classList.toggle('dark', value);
        });
        document.documentElement.classList.toggle('dark', darkMode);
    "
    class="h-full"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? trim($__env->yieldContent('title')) ?: 'EngiStart' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full overflow-hidden bg-apis-bg3 text-apis-text">

    @php
        $sidebarModules = config('sidebar.modules', []);
        $authUser = auth()->user();
        $currentSidebar = ($authUser && isset($sidebarModules[str_replace('_', '-', $authUser->role)]))
            ? $sidebarModules[str_replace('_', '-', $authUser->role)]
            : collect($sidebarModules)->first(function (array $module) {
            return isset($module['match']) && request()->routeIs($module['match']);
        }) ?? [
            'items' => [],
        ];
        $sidebarItems = $currentSidebar['items'] ?? [];

        $footerLabel = $authUser?->role === 'guest' ? 'Access level' : 'Signed in as';
        $footerName = $authUser?->name ?? 'Guest';
        $footerRole = $authUser?->role
            ? \Illuminate\Support\Str::of($authUser->role)->replace('_', ' ')->title()->toString()
            : 'Read-only';

        $badgeTones = [
            'blue' => ['bg' => 'var(--blue-bg)', 'color' => 'var(--blue)'],
            'amber' => ['bg' => 'var(--amber-bg)', 'color' => 'var(--amber)'],
            'green' => ['bg' => 'var(--green-bg)', 'color' => 'var(--green)'],
            'red' => ['bg' => 'var(--red-bg)', 'color' => 'var(--red)'],
        ];
    @endphp

    <div class="flex h-screen overflow-hidden bg-apis-bg3">

        {{-- ── SIDEBAR ─────────────────────────────────────────── --}}
        <aside class="flex w-56 flex-shrink-0 flex-col bg-apis-bg2"
               style="border-right: 0.5px solid var(--border)">

            {{-- Logo / Brand --}}
            <div class="px-5 py-4" style="border-bottom: 0.5px solid var(--border)">
                <h1 class="text-lg font-bold tracking-tight">EngiStart</h1>
                <p class="mt-0.5 text-[10px] leading-snug text-apis-text3">
                    Automated Project Initialization System
                </p>
            </div>

            {{-- Nav links --}}
            <nav class="flex-1 overflow-y-auto px-2 py-2.5 space-y-0.5">
                @foreach ($sidebarItems as $item)
                    @php
                        $isActive = collect($item['active'] ?? [$item['route']])->contains(fn ($pattern) => request()->routeIs($pattern));
                        $badge = $item['badge'] ?? null;
                        $tone = $badge ? ($badgeTones[$badge['tone'] ?? 'blue'] ?? $badgeTones['blue']) : null;
                    @endphp

                    <a href="{{ route($item['route']) }}"
                       class="flex items-center {{ $badge ? 'justify-between' : '' }} rounded-md px-3 py-2 text-sm {{ $isActive ? 'font-medium bg-apis-bg text-apis-text' : 'text-apis-text2 transition-colors hover:bg-apis-bg hover:text-apis-text' }}"
                       @if($isActive) style="border: 0.5px solid var(--border2)" @endif>
                        <span>{{ $item['label'] }}</span>
                        @if ($badge)
                            <span class="text-[10px] px-1.5 py-0.5 rounded" style="background: {{ $tone['bg'] }}; color: {{ $tone['color'] }}">{{ $badge['text'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            {{-- User / footer --}}
            <div class="px-4 py-3" style="border-top: 0.5px solid var(--border)">
                <p class="mb-1 text-[10px] text-apis-text3">{{ $footerLabel }}</p>
                <p class="text-xs font-medium leading-tight text-apis-text">{{ $footerName }}</p>
                <p class="mt-0.5 text-[11px] text-apis-blue">{{ $footerRole }}</p>
            </div>
        </aside>

        {{-- ── MAIN COLUMN ──────────────────────────────────────── --}}
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

            {{-- Topbar --}}
            <header class="flex flex-shrink-0 items-center justify-between bg-apis-bg px-6 py-3"
                    style="border-bottom: 0.5px solid var(--border)">
                <div>
                    <span class="text-sm font-medium text-apis-text">
                        {{ $header ?? trim($__env->yieldContent('header')) }}
                    </span>
                    @if (! empty($subheader ?? trim($__env->yieldContent('subheader'))))
                        <p class="text-xs text-apis-text2 mt-0.5">
                            {{ $subheader ?? trim($__env->yieldContent('subheader')) }}
                        </p>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded px-3 py-1 text-xs text-apis-text2 hover:bg-apis-bg2 transition-colors"
                                style="border: 0.5px solid var(--border2)"
                            >
                                Logout
                            </button>
                        </form>
                    @endauth

                    <button
                        type="button"
                        @click="darkMode = !darkMode"
                        class="rounded px-3 py-1 text-xs text-apis-text2 hover:bg-apis-bg2 transition-colors"
                        style="border: 0.5px solid var(--border2)"
                    >
                        <span x-show="!darkMode">Dark Mode</span>
                        <span x-show="darkMode">Light Mode</span>
                    </button>

                    @yield('headerRight')
                </div>
            </header>

            {{-- Scrollable content --}}
            <main class="flex-1 overflow-y-auto bg-apis-bg">
                @if (isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>
        </div>

        <livewire:shared.confirmation-modal />

    </div>

    @livewireScripts
</body>
</html>