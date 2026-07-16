<?php

namespace App\Livewire\Shared;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public array $notifications = [];

    public int $unreadCount = 0;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->notifications = $user->notifications()->latest()->take(20)->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'read_at' => optional($notification->read_at)->toISOString(),
                'created_at' => $notification->created_at->toISOString(),
                'data' => $notification->data,
            ])
            ->all();

        $this->unreadCount = $user->unreadNotifications()->count();
    }

    protected function getListeners(): array
    {
        $userId = Auth::id();

        if (! $userId) {
            return [];
        }

        return [
            "echo-notification:App.Models.User.{$userId}" => 'notificationReceived',
        ];
    }

    public function notificationReceived(array $notification): void
    {
        // Broadcast payloads arrive flat; DB rows nest fields under `data`. Normalize so the view only ever deals with one shape.
        $normalized = [
            'id' => $notification['id'] ?? (string) \Illuminate\Support\Str::uuid(),
            'read_at' => null,
            'created_at' => now()->toISOString(),
            'data' => [
                'request_id' => $notification['data']['request_id'] ?? $notification['request_id'] ?? null,
                'request_number' => $notification['data']['request_number'] ?? $notification['request_number'] ?? null,
                'event' => $notification['data']['event'] ?? $notification['event'] ?? null,
                'title' => $notification['data']['title'] ?? $notification['title'] ?? 'Notification',
                'body' => $notification['data']['body'] ?? $notification['body'] ?? '',
                'url' => $notification['data']['url'] ?? $notification['url'] ?? null,
            ],
        ];

        array_unshift($this->notifications, $normalized);
        $this->unreadCount++;
    }

    public function markRead(string $id): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $notification = $user->notifications()->whereKey($id)->first();

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
            $this->unreadCount = max(0, $this->unreadCount - 1);
        }

        $this->notifications = collect($this->notifications)
            ->map(function (array $item) use ($id) {
                if ($item['id'] === $id && ! $item['read_at']) {
                    $item['read_at'] = now()->toISOString();
                }

                return $item;
            })
            ->all();
    }

    public function markAllRead(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $user->unreadNotifications()->update(['read_at' => now()]);
        $this->unreadCount = 0;

        $this->notifications = collect($this->notifications)
            ->map(function (array $item) {
                $item['read_at'] = $item['read_at'] ?? now()->toISOString();

                return $item;
            })
            ->all();
    }

    public function render()
    {
        return view('livewire.shared.notification-bell');
    }
}
