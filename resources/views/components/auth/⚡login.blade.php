<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('components.layouts.guest')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password, 'is_active' => true], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        Auth::user()->forceFill(['last_login_at' => now()])->saveQuietly();

        $default = Auth::user()->hasRole('super_admin')
            ? route('platform.dashboard')
            : route('dashboard');

        $this->redirectIntended(default: $default, navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-semibold">{{ __('auth.sign_in') }}</h2>
        <p class="mt-1 text-sm text-base-content/60">{{ __('auth.sign_in_subtitle') }}</p>
    </div>

    <form wire:submit="login" class="space-y-5">
        <x-ui.input
            :label="__('auth.email')"
            wire:model="email"
            type="email"
            icon="o-envelope"
            autocomplete="email"
            autofocus
        />

        <x-ui.password
            :label="__('auth.password_label')"
            wire:model="password"
            icon="o-lock-closed"
            autocomplete="current-password"
            right
        />

        <div class="flex items-center justify-between">
            <x-ui.checkbox :label="__('auth.remember_me')" wire:model="remember" />
        </div>

        <x-ui.button
            :label="__('auth.sign_in')"
            type="submit"
            class="btn-primary w-full"
            spinner="login"
        />
    </form>
</div>
