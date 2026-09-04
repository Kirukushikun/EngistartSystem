<?php

namespace App\Livewire\ITAdmin;

use App\Livewire\Concerns\HasSimplePagination;
use App\Models\User;
use App\Support\TestingMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class UsersPage extends Component
{
    use HasSimplePagination;

    public $dbUsers = [];

    public string $search = '';

    public string $statusFilter = 'all';

    public string $roleFilter = 'all';

    public string $sortBy = 'name_asc';

    public int $perPage = 10;

    public int $page = 1;

    public ?string $formMode = null;

    public ?int $selectedUserId = null;

    public string $selectedUserName = '';

    public string $selectedUserEmail = '';

    public array $form = [
        'role' => 'farm_manager',
        'farm' => '',
        'department' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.role' => ['required', 'string'],
            'form.farm' => ['nullable', 'string', 'max:255'],
            'form.department' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedStatusFilter(): void
    {
        $this->page = 1;
    }

    public function updatedRoleFilter(): void
    {
        $this->page = 1;
    }

    public function updatedSortBy(): void
    {
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function mount(): void
    {
        $this->refreshDbUsers();
    }

    public function getUsersProperty(): Collection
    {
        // Testing mode has no real directory to grant access from -- test
        // accounts already have their roles from TestAccountsSeeder. Show the
        // local roster directly instead of calling the real API with whatever
        // placeholder credentials happen to be sitting in a dev .env.
        if (TestingMode::enabled()) {
            return $this->dbUsers->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'farm' => $user->farm ?? '—',
                'department' => $user->department ?? '—',
                'status' => $user->is_active ? 'active' : 'disabled',
            ])->values();
        }

        $apiUsers = Cache::remember('users_page_api_users', 300, function () {
            $baseUri = rtrim((string) config('auth.api.base_uri'), '/');
            $apiKey = (string) config('auth.api.auth_user_api_key');

            if ($baseUri === '' || $apiKey === '') {
                Log::warning('UsersPage skipped: auth configuration is incomplete.');
                return [];
            }

            try {
                $response = Http::withHeaders([
                        'x-api-key' => $apiKey,
                    ])
                    ->withOptions([
                        'verify' => storage_path('cacert.pem'),
                    ])
                    ->timeout(10)
                    ->connectTimeout(5)
                    ->post($baseUri . '/api/v1/users');
            } catch (\Throwable $exception) {
                Log::error('UsersPage API unreachable: ' . $exception->getMessage());
                return [];
            }

            if (!$response->successful()) {
                Log::error('UsersPage API error: ' . $response->status());
                return [];
            }

            $json  = $response->json();
            $users = $json['data'] ?? $json;

            $decrypted = [];

            foreach ($users as $user) {
                try {
                    $user['id'] = Crypt::decryptString($user['id']);
                    $decrypted[] = $user;
                } catch (\Exception $e) {
                    // Drop the record rather than keep the raw ciphertext in
                    // 'id' -- it would otherwise get rendered straight into
                    // wire:click="grantAccess(...)" and break every button on
                    // the page. Usually an APP_KEY/cipher mismatch with
                    // whichever system encrypted this id; see the message.
                    Log::error('Failed to decrypt user id for '.($user['first_name'] ?? '?').' '.($user['last_name'] ?? '?').': '.$e->getMessage());
                }
            }

            return $decrypted;
        });

        return collect($apiUsers)->map(function ($user) {
            $dbUser = $this->dbUsers->get($user['id']);

            return [
                'id'     => $user['id'],
                'name'   => $user['first_name'] . ' ' . $user['last_name'],
                'email'  => $user['email'],
                'role'   => $dbUser?->role ?? '—',
                'farm'   => $dbUser?->farm ?? '—',
                'department' => $dbUser?->department ?? '—',
                'status' => $dbUser ? ($dbUser->is_active ? 'active' : 'disabled') : 'no access',
            ];
        });
    }

    public function getFilteredUsersProperty(): Collection
    {
        $items = $this->users;

        if ($this->search !== '') {
            $needle = mb_strtolower(trim($this->search));

            $items = $items->filter(function (array $user) use ($needle): bool {
                return str_contains(mb_strtolower((string) $user['name']), $needle)
                    || str_contains(mb_strtolower((string) $user['email']), $needle)
                    || str_contains(mb_strtolower((string) $user['role']), $needle)
                    || str_contains(mb_strtolower((string) $user['farm']), $needle)
                    || str_contains(mb_strtolower((string) $user['department']), $needle)
                    || str_contains((string) $user['id'], $needle);
            })->values();
        }

        if ($this->statusFilter !== 'all') {
            $items = $items->where('status', $this->statusFilter)->values();
        }

        if ($this->roleFilter !== 'all') {
            $items = $items->where('role', $this->roleFilter)->values();
        }

        return match ($this->sortBy) {
            'name_desc' => $items->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'email_asc' => $items->sortBy('email', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'email_desc' => $items->sortByDesc('email', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'status' => $items->sortBy('status', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            default => $items->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
        };
    }

    public function getPaginatedUsersProperty(): Collection
    {
        if ($this->page > $this->totalPages) {
            $this->page = $this->totalPages;
        }

        return $this->filteredUsers
            ->slice(($this->page - 1) * $this->perPage, $this->perPage)
            ->values();
    }

    protected function paginationSourceCount(): int
    {
        return $this->filteredUsers->count();
    }

    public function grantAccess(int $userId): void
    {
        $user = $this->users->firstWhere('id', $userId);

        if (! $user || $user['status'] !== 'no access') {
            return;
        }

        $this->selectedUserId = $userId;
        $this->selectedUserName = (string) $user['name'];
        $this->selectedUserEmail = (string) $user['email'];
        $this->formMode = 'grant';
        $this->form = [
            'role' => 'farm_manager',
            'farm' => '',
            'department' => '',
        ];

        $this->resetValidation();
    }

    public function getIsModalOpenProperty(): bool
    {
        return $this->formMode !== null;
    }

    public function editUser(int $userId): void
    {
        $user = $this->users->firstWhere('id', $userId);

        if (! $user || $user['status'] === 'no access') {
            return;
        }

        $this->selectedUserId = $userId;
        $this->selectedUserName = (string) $user['name'];
        $this->selectedUserEmail = (string) $user['email'];
        $this->formMode = 'edit';
        $this->form = [
            'role' => $user['role'] === '—' ? 'farm_manager' : (string) $user['role'],
            'farm' => $user['farm'] === '—' ? '' : (string) $user['farm'],
            'department' => $user['department'] === '—' ? '' : (string) $user['department'],
        ];

        $this->resetValidation();
    }

    public function editRole(int $userId): void
    {
        $user = $this->users->firstWhere('id', $userId);

        if (! $user || $user['status'] === 'no access') {
            return;
        }

        $this->selectedUserId = $userId;
        $this->selectedUserName = (string) $user['name'];
        $this->selectedUserEmail = (string) $user['email'];
        $this->formMode = 'role';
        $this->form = [
            'role' => $user['role'] === '—' ? 'farm_manager' : (string) $user['role'],
            'farm' => $user['farm'] === '—' ? '' : (string) $user['farm'],
            'department' => $user['department'] === '—' ? '' : (string) $user['department'],
        ];

        $this->resetValidation();
    }

    public function saveUser(): void
    {
        if (! $this->selectedUserId || ! in_array($this->formMode, ['grant', 'edit', 'role'], true)) {
            return;
        }

        $validated = $this->validate();
        $directoryUser = $this->users->firstWhere('id', $this->selectedUserId);

        if (! $directoryUser) {
            return;
        }

        $user = User::find($this->selectedUserId);

        if (! $user) {
            $user = new User();
            $user->id = $this->selectedUserId;
            $user->name = (string) $directoryUser['name'];
            $user->email = (string) $directoryUser['email'];
            $user->password = Hash::make(Str::random(40));
            $user->is_active = true;
        } else {
            $user->name = (string) $directoryUser['name'];
            $user->email = (string) $directoryUser['email'];
        }

        $wasActiveEngineer = $user->exists && $user->getOriginal('role') === 'engineer' && (bool) $user->getOriginal('is_active');

        $user->role = (string) $validated['form']['role'];

        if ($this->formMode !== 'role') {
            $user->farm = $this->blankToNull($validated['form']['farm']);
            $user->department = $this->blankToNull($validated['form']['department']);
        }

        if ($user->role === 'engineer' && $user->is_active && ! $wasActiveEngineer) {
            $activeEngineerCount = User::query()
                ->where('role', 'engineer')
                ->where('is_active', true)
                ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->id))
                ->count();

            if ($activeEngineerCount >= 4) {
                $user->is_active = false;
                $this->dispatch('notify', type: 'warn', message: '4 engineer accounts are already active — this one was added disabled. Disable another engineer to activate it.');
            }
        }

        $user->save();

        $this->refreshDbUsers();
        $this->cancelForm();
    }

    protected function activeEngineerCount(): int
    {
        return User::query()->where('role', 'engineer')->where('is_active', true)->count();
    }

    public function toggleAccess(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        if (! $user->is_active && $user->role === 'engineer' && $this->activeEngineerCount() >= 4) {
            $this->dispatch('notify', type: 'warn', message: 'Only 4 engineer accounts can be active at a time. Disable another engineer first.');

            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $this->refreshDbUsers();
    }

    public function cancelForm(): void
    {
        $this->formMode = null;
        $this->selectedUserId = null;
        $this->selectedUserName = '';
        $this->selectedUserEmail = '';
        $this->form = [
            'role' => 'farm_manager',
            'farm' => '',
            'department' => '',
        ];

        $this->resetValidation();
    }

    public function getRoleOptionsProperty(): array
    {
        return [
            'farm_manager' => 'Farm Manager',
            'division_head' => 'Division Head',
            'vp_gen_services' => 'VP Gen Services',
            'dh_gen_services' => 'DH Gen Services',
            'ed_manager' => 'ED Manager',
            'it_admin' => 'IT Admin',
            'guest' => 'Guest',
        ];
    }

    public function getFarmOptionsProperty(): array
    {
        return $this->optionsWithCurrentValue(
            (array) config('organization.farms', []),
            $this->form['farm'] ?? ''
        );
    }

    public function getDepartmentOptionsProperty(): array
    {
        return $this->optionsWithCurrentValue(
            (array) config('organization.departments', []),
            $this->form['department'] ?? ''
        );
    }

    /**
     * The configured list, plus the record's current value tacked on if it
     * predates that list (e.g. seeded demo data) -- so opening the edit form
     * doesn't silently drop it just because it isn't one of the fixed options.
     */
    protected function optionsWithCurrentValue(array $options, string $current): array
    {
        $current = trim($current);

        if ($current !== '' && ! in_array($current, $options, true)) {
            $options[] = $current;
        }

        return $options;
    }

    protected function refreshDbUsers(): void
    {
        Cache::forget('users_page_db_users');

        $this->dbUsers = Cache::remember('users_page_db_users', 600, function () {
            return User::all()->keyBy('id');
        });

        // Livewire memoizes computed properties for the whole request. saveUser()
        // and toggleAccess() read $this->users before they mutate anything, so
        // without this the re-render replays that pre-save value -- the row keeps
        // saying "No Access" and the grant looks like it silently did nothing.
        unset($this->users, $this->filteredUsers, $this->paginatedUsers);
        unset($this->totalPages, $this->showingFrom, $this->showingTo);
    }

    protected function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function render()
    {
        return view('livewire.it-admin.users-page')
            ->layout('layouts.app', [
                'title'     => 'User Management | Project Initialization System',
                'header'    => 'User Management',
                'subheader' => 'Maintain access and roles for system users.',
            ]);
    }
}