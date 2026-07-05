<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationSummaryController extends Controller
{
    /** Lightweight JSON poll for the topbar bell (no Inertia). */
    public function __invoke(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['unread' => 0, 'items' => []]);
        }

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->latest()->limit(8)->get()
                ->map(fn ($note) => [
                    'id' => $note->id,
                    'read_at' => $note->read_at?->toIso8601String(),
                    'created_at' => $note->created_at?->diffForHumans(),
                    'data' => $note->data,
                ])
                ->all(),
            'sound' => (bool) data_get($user->settings, 'notifications.sound', true),
        ]);
    }
}
