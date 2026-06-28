<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->hasRole('super_admin')) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant instanceof Tenant || $tenant->onboarding_completed_at !== null) {
            return $next($request);
        }

        if ($this->allowsDuringOnboarding($request)) {
            return $next($request);
        }

        return redirect()->route('onboarding.index');
    }

    protected function allowsDuringOnboarding(Request $request): bool
    {
        if ($request->routeIs('onboarding.*', 'logout', 'locale.switch')) {
            return true;
        }

        // Livewire update/upload/preview must pass through or wizard actions never run.
        $routeName = (string) $request->route()?->getName();

        return str_contains($routeName, 'livewire');
    }
}
