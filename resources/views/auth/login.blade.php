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
                <p class="m-0 font-medium text-apis-text">Demo password: <code class="demo-password">1234</code></p>

                <div class="mt-2 text-apis-text2">
                    <p class="m-0 mb-1 font-medium text-apis-text">Example logins</p>

                    <div class="demo-logins">
                        <?php
                        $logins = [
                            'Farm Manager'      => 'j.santos@brooksidegroup.org',
                            'Division Head'     => 'dh.santos@brooksidegroup.org',
                            'VP Gen Services'   => 't.dizon@brooksidegroup.org',
                            'DH Gen Services'   => 'a.roque@brooksidegroup.org',
                            'ED Manager'        => 'd.baniaga@brooksidegroup.org',
                            'IT Admin'          => 'j.montiano@brooksidegroup.org',
                            'Guest'             => 'guest@brooksidegroup.org',
                        ];

                        foreach ($logins as $role => $email): ?>
                            <div class="demo-login-row">
                                <span class="demo-role"><?= htmlspecialchars($role) ?></span>
                                <span class="copy-email" data-email="<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <style>
                    .demo-password {
                        font-family: monospace;
                        background: var(--border);
                        padding: 0px 5px;
                        border-radius: 4px;
                    }
                    .demo-logins {
                        display: flex;
                        flex-direction: column;
                        gap: 2px;
                    }
                    .demo-login-row {
                        display: grid;
                        grid-template-columns: 110px 1fr;
                        align-items: center;
                        gap: 8px;
                    }
                    .demo-role {
                        color: var(--apis-text2);
                        opacity: 0.6;
                        white-space: nowrap;
                    }
                    .copy-email {
                        color: var(--blue-400, #60a5fa);
                        cursor: pointer;
                        transition: opacity 0.15s;
                    }
                    .copy-email:hover {
                        opacity: 0.75;
                    }
                    .copy-email.copied {
                        color: var(--green-400, #4ade80);
                    }
                </style>

                <script>
                    document.querySelectorAll('.copy-email').forEach(el => {
                        const original = el.textContent;
                        el.addEventListener('click', () => {
                            navigator.clipboard.writeText(el.dataset.email).then(() => {
                                el.textContent = 'Copied!';
                                el.classList.add('copied');
                                setTimeout(() => {
                                    el.textContent = original;
                                    el.classList.remove('copied');
                                }, 1500);
                            });
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</body>
</html>
