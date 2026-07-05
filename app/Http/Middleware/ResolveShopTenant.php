<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Shop\ShopContext;
use App\Services\Shop\ShopSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveShopTenant
{
    public function __construct(
        protected ShopSettingsService $settings,
        protected ShopContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantParam = $request->route('tenant');

        $tenant = $tenantParam instanceof Tenant
            ? $tenantParam
            : Tenant::query()->where('slug', (string) $tenantParam)->first();

        if (! $tenant instanceof Tenant || ! $tenant->is_active) {
            abort(404);
        }

        $shop = $this->settings->forTenant($tenant);

        if (! ($shop['enabled'] ?? false)) {
            $user = $request->user();
            $canPreview = $user
                && (int) $user->tenant_id === (int) $tenant->id
                && $user->can('settings.manage');

            if (! $canPreview) {
                abort(404);
            }
        }

        $this->context->setTenant($tenant, $shop);
        app()->instance(ShopContext::class, $this->context);

        $locale = (string) ($tenant->settings['locale'] ?? config('app.locale', 'ar'));
        if (in_array($locale, ['ar', 'en'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
