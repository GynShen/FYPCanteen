<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class CustomerNotificationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeCustomer($request);

        return inertia('Customer/Notifications', [
            'notifications' => $request->user()->notifications()->latest()->paginate(10)->through(fn ($notification) => [
                'id' => $notification->id,
                'type' => $notification->data['event'] ?? null,
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? '',
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at->toISOString(),
            ]),
        ]);
    }

    public function markRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $this->authorizeCustomer($request);
        abort_unless((int) $notification->notifiable_id === $request->user()->id && $notification->notifiable_type === $request->user()::class, 403);
        $notification->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->authorizeCustomer($request);
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    private function authorizeCustomer(Request $request): void
    {
        abort_unless($request->user()?->hasRole('customer'), 403);
    }
}
