<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Notifications')] class extends Component
{
    use UiToast, WithPagination;

    public string $filter = 'all';

    public bool $emailAlerts = false;

    public function mount(): void
    {
        $this->emailAlerts = (bool) data_get(Auth::user()?->settings, 'notifications.mail', false);
    }

    public function savePreferences(): void
    {
        $user = Auth::user();
        if ($user) {
            $settings = $user->settings ?? [];
            $settings['notifications'] = array_merge($settings['notifications'] ?? [], ['mail' => $this->emailAlerts]);
            $user->forceFill(['settings' => $settings])->save();
        }

        $this->success(__('notifications.saved'));
    }

    public function markRead(string $id): void
    {
        Auth::user()?->notifications()->whereKey($id)->first()?->markAsRead();
    }

    public function markAllRead(): void
    {
        Auth::user()?->unreadNotifications->markAsRead();
        $this->success(__('notifications.saved'));
    }

    public function with(): array
    {
        $user = Auth::user();
        $query = $user
            ? $user->notifications()->when($this->filter === 'unread', fn ($q) => $q->whereNull('read_at'))->latest()
            : null;

        return [
            'notifications' => $query ? $query->paginate(15) : collect(),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.header :title="__('notifications.title')" :subtitle="__('notifications.subtitle')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('notifications.mark_all_read')" icon="o-check" wire:click="markAllRead" class="btn-ghost" />
        </x-slot:actions>
    </x-ui.header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="flex gap-2">
                <x-ui.button :label="__('notifications.all')" wire:click="$set('filter', 'all')"
                    class="btn-sm {{ $filter === 'all' ? 'btn-primary' : 'btn-ghost' }}" />
                <x-ui.button :label="__('notifications.unread')" wire:click="$set('filter', 'unread')"
                    class="btn-sm {{ $filter === 'unread' ? 'btn-primary' : 'btn-ghost' }}" />
            </div>

            <x-ui.card>
                <div class="divide-y divide-base-300">
                    @forelse ($notifications as $note)
                        @php($data = $note->data)
                        <div class="flex items-start gap-3 py-3 {{ $note->read_at ? 'opacity-60' : '' }}">
                            <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full
                                @class([
                                    'bg-error/10 text-error' => ($data['level'] ?? '') === 'alert',
                                    'bg-warning/10 text-warning' => ($data['level'] ?? '') === 'reminder',
                                    'bg-info/10 text-info' => ($data['level'] ?? '') === 'info',
                                    'bg-success/10 text-success' => ($data['level'] ?? '') === 'success',
                                ])">
                                <x-ui.icon :name="$data['icon'] ?? 'o-bell'" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ $data['title'] ?? '' }}</span>
                                    @if (! $note->read_at)
                                        <x-ui.badge :value="__('notifications.' . 'level_' . ($data['level'] ?? 'info'))" class="badge-sm badge-primary" />
                                    @endif
                                </div>
                                <p class="text-sm text-base-content/70">{{ $data['message'] ?? '' }}</p>
                                <p class="text-xs text-base-content/40">{{ $note->created_at?->diffForHumans() }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if (! empty($data['url']))
                                    <a href="{{ $data['url'] }}" wire:navigate class="btn btn-ghost btn-xs">{{ __('notifications.view') }}</a>
                                @endif
                                @if (! $note->read_at)
                                    <x-ui.button icon="o-check" wire:click="markRead('{{ $note->id }}')" class="btn-text btn-circle btn-xs" tooltip="{{ __('notifications.mark_read') }}" />
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-base-content/50">{{ __('notifications.none') }}</div>
                    @endforelse
                </div>

                @if (! ($notifications instanceof \Illuminate\Support\Collection))
                    <div class="mt-4 border-t border-base-content/10 pt-4">{{ $notifications->links() }}</div>
                @endif
            </x-ui.card>
        </div>

        <div>
            <x-ui.card :title="__('notifications.preferences')" separator>
                <fieldset class="fieldset gap-3 p-0">
                    <legend class="fieldset-legend text-sm font-semibold">{{ __('notifications.preferences') }}</legend>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-[var(--radius-field)] border border-base-content/10 bg-base-200/30 px-3 py-2.5">
                        <div>
                            <span class="text-sm font-medium">{{ __('notifications.email_alerts') }}</span>
                            <p class="text-xs text-base-content/60">{{ __('notifications.email_alerts_hint') }}</p>
                        </div>
                        <input type="checkbox" class="toggle toggle-primary toggle-sm" wire:model="emailAlerts" />
                    </label>
                </fieldset>
                <x-slot:actions>
                    <x-ui.button :label="__('common.save')" icon="o-check" wire:click="savePreferences" spinner class="btn-primary btn-sm" />
                </x-slot:actions>
            </x-ui.card>
        </div>
    </div>
</div>
