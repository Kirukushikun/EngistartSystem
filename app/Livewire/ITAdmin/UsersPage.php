<?php

namespace App\Livewire\ITAdmin;

use App\Models\User;
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
        $apiUsers = Cache::remember('users_page_api_users', 300, function () {
            $response = Http::withHeaders([
                    'x-api-key' => '123456789bgc',
                ])
                ->withOptions([
                    'verify' => storage_path('cacert.pem'),
                ])
                ->post('https://bfcgroup.ph/api/v1/users');

            if (!$response->successful()) {
                Log::error('UsersPage API error: ' . $response->status());
                return [];
            }

            $json  = $response->json();
            $users = $json['data'] ?? $json;

            return array_map(function ($user) {
                try {
                    $user['id'] = Crypt::decryptString($user['id']);
                } catch (\Exception $e) {
                    Log::error('Failed to decrypt ID for: ' . $user['first_name'] . ' ' . $user['last_name']);
                }
                return $user;
            }, $users);
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

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->filteredUsers->count() / $this->perPage));
    }

    public function getShowingFromProperty(): int
    {
        return $this->filteredUsers->isEmpty() ? 0 : (($this->page - 1) * $this->perPage) + 1;
    }

    public function getShowingToProperty(): int
    {
        if ($this->filteredUsers->isEmpty()) {
            return 0;
        }

        return min($this->page * $this->perPage, $this->filteredUsers->count());
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage(): void
    {
        if ($this->page < $this->totalPages) {
            $this->page++;
        }
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

        $user->role = (string) $validated['form']['role'];

        if ($this->formMode !== 'role') {
            $user->farm = $this->blankToNull($validated['form']['farm']);
            $user->department = $this->blankToNull($validated['form']['department']);
        }

        $user->save();

        $this->refreshDbUsers();
        $this->cancelForm();
    }

    public function toggleAccess(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
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

    protected function refreshDbUsers(): void
    {
        Cache::forget('users_page_db_users');

        $this->dbUsers = Cache::remember('users_page_db_users', 600, function () {
            return User::all()->keyBy('id');
        });
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
                'title'     => 'User Management | EngiStart',
                'header'    => 'User Management',
                'subheader' => 'Maintain access and roles for system users.',
            ]);
    }
}