<?php

namespace App\Notifications;

use App\Http\Controllers\AuthController;
use App\Models\ProjectRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ProjectRequest $request,
        public readonly string $event,
        public readonly string $title,
        public readonly string $body,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload($notifiable);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload($notifiable));
    }

    protected function payload(object $notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'request_number' => $this->request->request_number,
            'event' => $this->event,
            'title' => $this->title,
            'body' => $this->body,
            'url' => route(AuthController::homeRouteForRole((string) $notifiable->role)),
        ];
    }
}
