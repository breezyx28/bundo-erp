<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationActionController extends Controller
{
    /**
     * Mark a single notification as read, then follow its target url when present
     * (mirrors the old Livewire notification-bell markRead behavior).
     */
    public function read(string $id): RedirectResponse
    {
        $notification = Auth::user()?->notifications()->whereKey($id)->first();
        $notification?->markAsRead();

        $url = data_get($notification, 'data.url');

        if (is_string($url) && $url !== '') {
            return redirect()->to($url);
        }

        return redirect()->back();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function readAll(): RedirectResponse
    {
        Auth::user()?->unreadNotifications->markAsRead();

        return redirect()->back();
    }
}
