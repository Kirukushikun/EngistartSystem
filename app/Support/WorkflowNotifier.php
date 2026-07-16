<?php

namespace App\Support;

use App\Models\ProjectRequest;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

final class WorkflowNotifier
{
    /**
     * Notify whoever the request is now owned by (a whole role inbox, or one
     * specific user), based on current_owner_role/current_owner_id. Skips the
     * user who just performed the action that triggered this.
     */
    public static function notifyOwner(ProjectRequest $request, string $event, string $title, string $body): void
    {
        if (! $request->current_owner_role) {
            return;
        }

        $recipients = $request->current_owner_id
            ? User::query()->whereKey($request->current_owner_id)->get()
            : User::query()->where('role', $request->current_owner_role)->where('is_active', true)->get();

        $recipients = $recipients->reject(fn (User $user) => $user->id === Auth::id());

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new WorkflowNotification($request, $event, $title, $body));
    }

    /**
     * Notify a specific user regardless of current ownership (e.g. the
     * original requestor once the request has reached a terminal state).
     */
    public static function notifyUser(User $user, ProjectRequest $request, string $event, string $title, string $body): void
    {
        if ($user->id === Auth::id()) {
            return;
        }

        Notification::send($user, new WorkflowNotification($request, $event, $title, $body));
    }
}
