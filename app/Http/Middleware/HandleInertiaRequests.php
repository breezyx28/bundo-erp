<?php

namespace App\Http\Middleware;

use App\Services\Branch\BranchContext;
use App\Services\Navigation\NavBadgeService;
use App\Services\Tenancy\TenantContext;
use App\Support\Navigation;
use App\Support\TenantBranding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the asset version so Inertia can force a full reload on deploy.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props shared with every Inertia response.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $user = Auth::user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super_admin' => $user->hasRole('super_admin'),
                    'permissions' => $user->getAllPermissions()->pluck('name')->all(),
                    'roles' => $user->getRoleNames()->all(),
                ] : null,
            ],

            'locale' => [
                'current' => $locale,
                'dir' => $locale === 'ar' ? 'rtl' : 'ltr',
                'available' => ['ar', 'en'],
            ],

            'translations' => fn () => $this->translations($locale),

            'nav' => fn () => $this->navigation(),

            'branding' => fn () => $this->branding(),

            'branchContext' => fn () => $this->branchContext($user),

            'notifications' => fn () => $this->notifications($user),

            'flash' => [
                'toast' => $request->session()->get('ui.toast'),
            ],
        ];
    }

    /**
     * Branch scope shown in the topbar branch selector (non-platform only).
     *
     * @return array<string, mixed>|null
     */
    protected function branchContext($user): ?array
    {
        if (! $user || app(TenantContext::class)->isPlatformMode()) {
            return null;
        }

        $context = app(BranchContext::class);

        return [
            'current_label' => $context->isConsolidated()
                ? __('nav.all_branches')
                : ($context->currentBranch()?->name ?? __('nav.all_branches')),
            'can_view_all' => $context->canViewAllBranches(),
            'is_consolidated' => $context->isConsolidated(),
            'branches' => $context->allowedBranches()
                ->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->name])
                ->values()
                ->all(),
        ];
    }

    /**
     * Recent + unread notification summary for the topbar bell.
     *
     * @return array<string, mixed>
     */
    protected function notifications($user): array
    {
        if (! $user) {
            return ['unread' => 0, 'items' => []];
        }

        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->latest()->limit(8)->get()
                ->map(fn ($note) => [
                    'id' => $note->id,
                    'read_at' => $note->read_at?->toIso8601String(),
                    'created_at' => $note->created_at?->diffForHumans(),
                    'data' => $note->data,
                ])
                ->all(),
        ];
    }

    /**
     * Load every translation group for the active locale as a nested object.
     *
     * @return array<string, mixed>
     */
    protected function translations(string $locale): array
    {
        $dir = lang_path($locale);

        if (! File::isDirectory($dir)) {
            return [];
        }

        $groups = [];

        foreach (File::files($dir) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group = $file->getFilenameWithoutExtension();
            $groups[$group] = trans($group, [], $locale);
        }

        return $groups;
    }

    /**
     * Build the navigation menu (already filtered by module + permission).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function navigation(): array
    {
        $current = request()->route()?->getName();
        $user = Auth::user();
        $badges = $user && ! app(TenantContext::class)->isPlatformMode()
            ? app(NavBadgeService::class)->badges($user)
            : [];

        return app(Navigation::class)->menu()->map(function (array $item) use ($current, $badges) {
            $routeName = $item['route'] ?? null;

            return [
                'label' => __($item['label']),
                'icon' => $item['icon'],
                'route' => $routeName,
                'href' => $routeName && Route::has($routeName) ? route($routeName) : null,
                'active' => $routeName !== null && $current === $routeName,
                'badge' => $routeName !== null ? ($badges[$routeName] ?? null) : null,
            ];
        })->all();
    }

    /**
     * Active tenant/branch white-label branding.
     *
     * @return array<string, mixed>
     */
    protected function branding(): array
    {
        $brand = app(TenantBranding::class);

        return [
            'company' => $brand->companyName(),
            'logo' => $brand->logoUrl(),
            'primary' => $brand->primaryColor(),
            'secondary' => $brand->secondaryColor(),
        ];
    }
}
