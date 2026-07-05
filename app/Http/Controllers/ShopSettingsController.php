<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\Shop\ShopSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ShopSettingsController extends Controller
{
    use InteractsWithToast;

    public function index(ShopSettingsService $shopSettings): Response
    {
        Gate::authorize('settings.manage');

        $tenant = Auth::user()?->tenant;

        abort_unless($tenant instanceof Tenant, 404);

        $settings = $shopSettings->forTenant($tenant);

        return Inertia::render('Shop/Settings', [
            'settings' => $settings,
            'public_url' => route('shop.index', ['tenant' => $tenant->slug]),
            'slug' => $tenant->slug,
            'shop_enabled' => (bool) ($settings['enabled'] ?? false),
        ]);
    }

    public function save(Request $request, ShopSettingsService $shopSettings): RedirectResponse
    {
        Gate::authorize('settings.manage');

        $tenant = Auth::user()?->tenant;
        abort_unless($tenant instanceof Tenant, 404);

        $request->merge([
            'enabled' => filter_var($request->input('enabled', false), FILTER_VALIDATE_BOOLEAN),
            'show_prices' => filter_var($request->input('show_prices', true), FILTER_VALIDATE_BOOLEAN),
        ]);

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'show_prices' => ['required', 'boolean'],
            'hero_title' => [Rule::requiredIf($request->boolean('enabled')), 'nullable', 'string', 'max:120'],
            'hero_subtitle' => ['nullable', 'string', 'max:240'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
            'share_message' => ['nullable', 'string', 'max:500'],
            'contact' => ['nullable', 'array'],
            'contact.phone' => ['nullable', 'string', 'max:40'],
            'contact.whatsapp' => ['nullable', 'string', 'max:40'],
            'contact.instagram' => ['nullable', 'string', 'max:120'],
            'contact.facebook' => ['nullable', 'string', 'max:120'],
            'contact.tiktok' => ['nullable', 'string', 'max:120'],
            'contact.address' => ['nullable', 'string', 'max:240'],
            'contact.email' => ['nullable', 'email', 'max:120'],
            'banners' => ['nullable', 'array', 'max:6'],
            'banners.*.title' => ['nullable', 'string', 'max:80'],
            'banners.*.link' => ['nullable', 'url', 'max:240'],
            'banners.*.image' => ['nullable', 'image', 'max:4096'],
        ]);

        $existing = $shopSettings->forTenant($tenant);

        if ($request->hasFile('hero_image')) {
            $data['hero_image'] = $request->file('hero_image')->store('shop/'.$tenant->id, 'public');
        } else {
            $data['hero_image'] = $existing['hero_image'];
        }

        $banners = [];
        foreach ($data['banners'] ?? [] as $index => $banner) {
            $image = $existing['banners'][$index]['image'] ?? null;
            if ($request->hasFile("banners.{$index}.image")) {
                $image = $request->file("banners.{$index}.image")->store('shop/'.$tenant->id, 'public');
            }
            $banners[] = [
                'title' => $banner['title'] ?? '',
                'link' => $banner['link'] ?? null,
                'image' => $image,
            ];
        }
        $data['banners'] = $banners;
        $data['contact'] = array_merge($shopSettings->defaults()['contact'], $data['contact'] ?? []);

        $shopSettings->save($tenant, $data);

        $this->toastSuccess(__('shop.settings_saved'));

        return redirect()->route('shop.settings');
    }

    public function toggleProduct(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('products.view');

        $data = $request->validate([
            'show_in_shop' => 'sometimes|boolean',
            'featured_in_shop' => 'sometimes|boolean',
        ]);

        $product->update($data);

        return redirect()->back();
    }
}
