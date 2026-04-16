<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EngiStart</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full overflow-hidden bg-apis-bg3 text-apis-text" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', value => { localStorage.setItem('darkMode', value); document.documentElement.classList.toggle('dark', value); }); document.documentElement.classList.toggle('dark', darkMode);">
    <div class="min-h-screen flex items-center justify-center px-6 py-10 bg-apis-bg3">
        <div class="w-full max-w-[380px] rounded-[16px] p-10 bg-apis-bg" style="border: 0.5px solid var(--border2)">
            <div class="text-center mb-8">
                <div class="text-[10px] tracking-[0.14em] uppercase text-apis-text2 mb-2">Brookside Group of Companies</div>
                <div class="text-[42px] font-bold tracking-[-0.04em] text-apis-text">EngiStart</div>
                <div class="text-[11px] text-apis-text2 mt-1">Automated Project Initialization System</div>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-[10px] px-4 py-3 text-[12px]" style="background: var(--red-bg); color: var(--red); border: 0.5px solid var(--red-bd)">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[12px] text-apis-text mb-1.5">Email address</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full h-[38px] rounded-[8px] px-3 text-[12px]" style="border: 0.5px solid var(--border2); background: var(--bg); color: var(--text)" placeholder="yourname@brooksidegroup.org" required autofocus>
                </div>
                <div>
                    <label class="block text-[12px] text-apis-text mb-1.5">Password</label>
                    <input type="password" name="password" class="w-full h-[38px] rounded-[8px] px-3 text-[12px]" style="border: 0.5px solid var(--border2); background: var(--bg); color: var(--text)" placeholder="Enter password" required>
                </div>
                <label class="flex items-center gap-2 text-[12px] text-apis-text2">
                    <input type="checkbox" name="remember" value="1">
                    <span>Remember me</span>
                </label>
                <button type="submit" class="w-full rounded-[8px] px-4 py-2.5 text-[13px] font-medium" style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd)">Sign In</button>
            </form>

            <div class="mt-5 rounded-[10px] px-4 py-3 text-[11px] leading-[1.6]" style="background: var(--bg2); border: 0.5px solid var(--border)">
                <p class="m-0 text-apis-text"><span class="font-medium">Demo password:</span> 1234</p>
                <p class="m-0 mt-1 text-apis-text2">Example logins: `j.santos@brooksidegroup.org`, `dh.santos@brooksidegroup.org`, `t.dizon@brooksidegroup.org`, `a.roque@brooksidegroup.org`, `d.baniaga@brooksidegroup.org`, `j.montiano@brooksidegroup.org`, `guest@brooksidegroup.org`</p>
            </div>
        </div>
    </div>
</body>
</html>
