<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    use InteractsWithToast;

    public function index(Request $request): Response
    {
        $filter = (string) ($request->string('filter') ?: 'all');
        $user = Auth::user();

        $notifications = $user
            ? $user->notifications()
                ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn ($note) => [
                    'id' => $note->id,
                    'level' => data_get($note->data, 'level', 'info'),
                    'icon' => data_get($note->data, 'icon'),
                    'title' => data_get($note->data, 'title', ''),
                    'message' => data_get($note->data, 'message', ''),
                    'url' => data_get($note->data, 'url'),
                    'read' => $note->read_at !== null,
                    'created_at' => $note->created_at?->diffForHumans(),
                ])
            : ['data' => []];

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'emailAlerts' => (bool) data_get($user?->settings, 'notifications.mail', false),
        ]);
    }

    public function markRead(string $id): RedirectResponse
    {
        Auth::user()?->notifications()->whereKey($id)->first()?->markAsRead();

        return redirect()->back();
    }

    public function markAllRead(): RedirectResponse
    {
        Auth::user()?->unreadNotifications->markAsRead();

        $this->toastSuccess(__('notifications.saved'));

        return redirect()->back();
    }

    public function savePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate(['emailAlerts' => 'boolean']);

        $user = Auth::user();

        if ($user) {
            $settings = $user->settings ?? [];
            $settings['notifications'] = array_merge($settings['notifications'] ?? [], ['mail' => (bool) ($data['emailAlerts'] ?? false)]);
            $user->forceFill(['settings' => $settings])->save();
        }

        $this->toastSuccess(__('notifications.saved'));

        return redirect()->back();
    }
}
