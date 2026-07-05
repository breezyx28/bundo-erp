<?php

namespace App\Services\Shop;

use App\Models\Setting;
use App\Models\Tenant;

class ShopSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'enabled' => false,
            'show_prices' => true,
            'hero_title' => '',
            'hero_subtitle' => '',
            'hero_image' => null,
            'banners' => [],
            'contact' => [
                'phone' => '',
                'whatsapp' => '',
                'instagram' => '',
                'facebook' => '',
                'tiktok' => '',
                'address' => '',
                'email' => '',
            ],
            'share_message' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forTenant(Tenant $tenant): array
    {
        $settings = $this->defaults();

        Setting::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('branch_id')
            ->where('group', 'shop')
            ->get()
            ->each(function (Setting $row) use (&$settings) {
                $settings[$row->key] = $row->castValue();
            });

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Tenant $tenant, array $data): void
    {
        foreach ($data as $key => $value) {
            $type = is_array($value) ? 'json' : (is_bool($value) ? 'boolean' : 'string');

            Setting::updateOrCreate(
                ['tenant_id' => $tenant->id, 'branch_id' => null, 'group' => 'shop', 'key' => $key],
                ['value' => $type === 'json' ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string) $value), 'type' => $type],
            );
        }
    }
}
