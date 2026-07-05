<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\Shop\ShopContext;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request, ShopContext $shop): Response
    {
        $tenant = $shop->tenant();
        $settings = $shop->settings();
        $categoryId = $request->integer('category') ?: null;

        $productsQuery = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('show_in_shop', true)
            ->where('is_active', true)
            ->with(['category:id,name', 'brand:id,name', 'media'])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderBy('name');

        $featured = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('show_in_shop', true)
            ->where('featured_in_shop', true)
            ->where('is_active', true)
            ->with(['category:id,name', 'media'])
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn (Product $p) => $this->productCard($p, $settings, $tenant->slug));

        $categories = Category::query()
            ->where('tenant_id', $tenant->id)
            ->whereHas('products', fn ($q) => $q->where('show_in_shop', true)->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Shop/Index', [
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo' => $tenant->logo ? asset('storage/'.$tenant->logo) : null,
                'primary_color' => $tenant->primary_color,
                'secondary_color' => $tenant->secondary_color,
            ],
            'shop' => $this->shopPayload($settings),
            'featured' => $featured,
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'products' => $productsQuery->paginate(24)->withQueryString()->through(
                fn (Product $p) => $this->productCard($p, $settings, $tenant->slug),
            ),
            'filters' => ['category' => $categoryId],
            'seo' => [
                'title' => $settings['hero_title'] ?: $tenant->name,
                'description' => $settings['hero_subtitle'] ?: __('shop.catalog_description', ['name' => $tenant->name]),
                'image' => $settings['hero_image'] ? asset('storage/'.$settings['hero_image']) : null,
            ],
        ]);
    }

    public function show(Request $request, ShopContext $shop): Response
    {
        $tenant = $shop->tenant();
        $settings = $shop->settings();
        $productId = (int) $request->route('product');

        $item = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('show_in_shop', true)
            ->where('is_active', true)
            ->with(['category:id,name', 'brand:id,name', 'media', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->findOrFail($productId);

        return Inertia::render('Shop/Show', [
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo' => $tenant->logo ? asset('storage/'.$tenant->logo) : null,
                'primary_color' => $tenant->primary_color,
                'secondary_color' => $tenant->secondary_color,
            ],
            'shop' => $this->shopPayload($settings),
            'product' => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->shop_description ?: $item->description,
                'category' => $item->category?->name,
                'brand' => $item->brand?->name,
                'price' => ($settings['show_prices'] ?? true) ? Money::format($item->selling_price) : null,
                'price_raw' => (float) $item->selling_price,
                'images' => $item->getMedia('images')->map(fn ($m) => $m->getUrl())->all(),
                'variants' => $item->variants->map(fn ($v) => [
                    'sku' => $v->sku,
                    'label' => $v->label(),
                    'size' => $v->options['size'] ?? '',
                    'color' => $v->options['color'] ?? '',
                    'price' => ($settings['show_prices'] ?? true) ? Money::format($v->selling_price ?: $item->selling_price) : null,
                ])->all(),
            ],
            'seo' => [
                'title' => $item->name.' — '.$tenant->name,
                'description' => \Illuminate\Support\Str::limit(strip_tags((string) ($item->shop_description ?: $item->description)), 160),
                'image' => $item->getFirstMediaUrl('images') ?: null,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function shopPayload(array $settings): array
    {
        return [
            'show_prices' => (bool) ($settings['show_prices'] ?? true),
            'hero_title' => (string) ($settings['hero_title'] ?? ''),
            'hero_subtitle' => (string) ($settings['hero_subtitle'] ?? ''),
            'hero_image' => ! empty($settings['hero_image']) ? asset('storage/'.$settings['hero_image']) : null,
            'banners' => collect($settings['banners'] ?? [])->map(fn ($b) => [
                'title' => (string) ($b['title'] ?? ''),
                'image' => ! empty($b['image']) ? asset('storage/'.$b['image']) : null,
                'link' => $b['link'] ?? null,
            ])->all(),
            'contact' => $settings['contact'] ?? [],
            'share_message' => (string) ($settings['share_message'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function productCard(Product $product, array $settings, string $slug): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category?->name,
            'brand' => $product->brand?->name,
            'image' => $product->getFirstMediaUrl('images', 'thumb') ?: null,
            'price' => ($settings['show_prices'] ?? true) ? Money::format($product->selling_price) : null,
            'url' => route('shop.show', ['tenant' => $slug, 'product' => $product->id]),
        ];
    }
}
