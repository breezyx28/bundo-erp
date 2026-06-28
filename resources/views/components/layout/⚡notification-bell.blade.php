<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public function markRead(string $id): void
    {
        $notification = Auth::user()?->notifications()->whereKey($id)->first();
        $notification?->markAsRead();

        $url = data_get($notification, 'data.url');
        if (is_string($url) && $url !== '') {
            $this->redirect($url, navigate: true);
        }
    }

    public function markAllRead(): void
    {
        Auth::user()?->unreadNotifications->markAsRead();
    }

    public function with(): array
    {
        $user = Auth::user();

        return [
            'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
            'recent' => $user ? $user->notifications()->latest()->limit(8)->get() : collect(),
        ];
    }
}; ?>

<div wire:poll.60s>
    <x-ui.dropdown right>
        <x-slot:trigger>
            <button type="button" class="btn btn-text btn-circle relative min-h-10 min-w-10" aria-label="{{ __('common.notifications') }}">
                <div class="indicator">
                    @if ($unreadCount > 0)
                        <span class="indicator-item badge badge-error badge-xs size-2 p-0"></span>
                    @endif
                    <x-ui.icon name="o-bell" class="size-5.5" />
                </div>
            </button>
        </x-slot:trigger>

        <div class="flex items-center justify-between px-4 py-2">
            <p class="text-sm font-semibold">{{ __('notifications.title') }}</p>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllRead" class="text-xs text-primary hover:underline">{{ __('notifications.mark_all_read') }}</button>
            @endif
        </div>
        <x-ui.menu-separator />

        <div class="max-h-96 w-80 overflow-y-auto">
            @forelse ($recent as $note)
                @php($data = $note->data)
                <button type="button" wire:click="markRead('{{ $note->id }}')"
                    class="flex w-full items-start gap-3 px-4 py-3 text-start hover:bg-base-200 {{ $note->read_at ? 'opacity-60' : '' }}">
                    <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full
                        @class([
                            'bg-error/10 text-error' => ($data['level'] ?? '') === 'alert',
                            'bg-warning/10 text-warning' => ($data['level'] ?? '') === 'reminder',
                            'bg-info/10 text-info' => ($data['level'] ?? '') === 'info',
                            'bg-success/10 text-success' => ($data['level'] ?? '') === 'success',
                        ])">
                        <x-ui.icon :name="$data['icon'] ?? 'o-bell'" class="size-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="truncate text-sm font-medium">{{ $data['title'] ?? '' }}</span>
                            @if (! $note->read_at)
                                <span class="size-2 shrink-0 rounded-full bg-primary"></span>
                            @endif
                        </span>
                        <span class="block text-xs text-base-content/60">{{ $data['message'] ?? '' }}</span>
                        <span class="block text-[8px] text-base-content/40">{{ $note->created_at?->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <div class="px-4 py-8 text-center text-sm text-base-content/50">{{ __('notifications.none') }}</div>
            @endforelse
        </div>

        <x-ui.menu-separator />
        <a href="{{ route('notifications.index') }}" wire:navigate class="block px-4 py-2 text-center text-sm text-primary hover:bg-base-200">
            {{ __('notifications.view_all') }}
        </a>
    </x-ui.dropdown>
</div>
