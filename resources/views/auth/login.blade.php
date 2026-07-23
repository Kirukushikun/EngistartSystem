<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        document.documentElement.classList.toggle('dark', localStorage.getItem('darkMode') === 'true');
    </script>
    <link rel="icon" href="{{ asset('engistart.ico') }}" type="image/x-icon">
    <title>Login | EngiStart</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full overflow-hidden bg-apis-bg3 text-apis-text" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', value => { localStorage.setItem('darkMode', value); document.documentElement.classList.toggle('dark', value); });">
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
                    <input type="email" name="email" id="login-email" value="{{ old('email') }}" class="w-full h-[38px] rounded-[8px] px-3 text-[12px]" style="border: 0.5px solid var(--border2); background: var(--bg); color: var(--text)" placeholder="yourname@brooksidegroup.org" required autofocus>
                </div>
                <div>
                    <label class="block text-[12px] text-apis-text mb-1.5">Password</label>
                    <input type="password" name="password" id="login-password" class="w-full h-[38px] rounded-[8px] px-3 text-[12px]" style="border: 0.5px solid var(--border2); background: var(--bg); color: var(--text)" placeholder="Enter password" required>
                </div>
                <label class="flex items-center gap-2 text-[12px] text-apis-text2">
                    <input type="checkbox" name="remember" value="1">
                    <span>Remember me</span>
                </label>
                <button type="submit" class="w-full rounded-[8px] px-4 py-2.5 text-[13px] font-medium" style="background: var(--blue-bg); color: var(--blue); border: 0.5px solid var(--blue-bd)">Sign In</button>
            </form>

            <div class="mt-5 rounded-[10px] px-4 py-3 text-[11px] leading-[1.6]" style="background: var(--bg2); border: 0.5px solid var(--border)">
                <p class="m-0 mb-2 font-medium text-apis-text">Dev accounts</p>
                <p class="m-0 mb-2 text-apis-text2">Click a role to fill its credentials, then Sign In.</p>

                <div class="dev-account-grid">
                    <?php
                    $logins = [
                        'Farm Manager'      => ['name' => 'Jose Santos',       'email' => 'j.santos@brooksidegroup.org'],
                        'Division Head'     => ['name' => 'Div. Head Santos',  'email' => 'dh.santos@brooksidegroup.org'],
                        'VP Gen Services'   => ['name' => 'Atty. T. Dizon',    'email' => 't.dizon@brooksidegroup.org'],
                        'DH Gen Services'   => ['name' => 'Ancel Roque',       'email' => 'a.roque@brooksidegroup.org'],
                        'ED Manager'        => ['name' => 'Engr. D. Baniaga',  'email' => 'd.baniaga@brooksidegroup.org'],
                        'IT Admin'          => ['name' => 'Jeff Montiano',     'email' => 'j.montiano@brooksidegroup.org'],
                        'Guest'             => ['name' => 'Guest Viewer',      'email' => 'guest@brooksidegroup.org'],
                    ];

                    foreach ($logins as $role => $account): ?>
                        <button type="button" class="dev-account-card" data-email="<?= htmlspecialchars($account['email']) ?>" data-password="1234">
                            <span class="dev-account-role"><?= htmlspecialchars($role) ?></span>
                            <span class="dev-account-name"><?= htmlspecialchars($account['name']) ?></span>
                            <span class="dev-account-email"><?= htmlspecialchars($account['email']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <style>
                    .dev-account-grid {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 8px;
                    }
                    .dev-account-card {
                        display: flex;
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 2px;
                        padding: 8px 10px;
                        border-radius: 8px;
                        border: 0.5px solid var(--border2);
                        background: var(--bg);
                        cursor: pointer;
                        text-align: left;
                        transition: border-color 0.15s, background 0.15s;
                    }
                    .dev-account-card:hover {
                        border-color: var(--blue-bd, #3b82f6);
                        background: var(--bg2);
                    }
                    .dev-account-card.filled {
                        border-color: var(--green-bd, #22c55e);
                    }
                    .dev-account-role {
                        font-size: 10px;
                        font-weight: 600;
                        letter-spacing: 0.04em;
                        text-transform: uppercase;
                        color: var(--blue-400, #60a5fa);
                    }
                    .dev-account-name {
                        font-size: 12px;
                        color: var(--text);
                    }
                    .dev-account-email {
                        font-size: 10px;
                        color: var(--apis-text2);
                        opacity: 0.7;
                    }
                </style>

                <script>
                    document.querySelectorAll('.dev-account-card').forEach(card => {
                        card.addEventListener('click', () => {
                            document.getElementById('login-email').value = card.dataset.email;
                            document.getElementById('login-password').value = card.dataset.password;
                            document.querySelectorAll('.dev-account-card').forEach(c => c.classList.remove('filled'));
                            card.classList.add('filled');
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</body>
</html>
