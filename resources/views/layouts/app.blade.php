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
    <title>@yield('title', 'EngiStart')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full overflow-hidden bg-apis-bg3 text-apis-text">

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
                @yield('sidebar')
            </nav>

            {{-- User / footer --}}
            <div class="px-4 py-3" style="border-top: 0.5px solid var(--border)">
                @yield('sidebarFooter')
            </div>
        </aside>

        {{-- ── MAIN COLUMN ──────────────────────────────────────── --}}
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

            {{-- Topbar --}}
            <header class="flex flex-shrink-0 items-center justify-between bg-apis-bg px-6 py-3"
                    style="border-bottom: 0.5px solid var(--border)">
                <div>
                    <span class="text-sm font-medium text-apis-text">
                        @yield('header', '')
                    </span>
                    @hasSection('subheader')
                        <p class="text-xs text-apis-text2 mt-0.5">
                            @yield('subheader')
                        </p>
                    @endif
                </div>

                <div class="flex items-center gap-3">
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
                @yield('content')
            </main>
        </div>

    </div>

    @livewireScripts
</body>
</html>