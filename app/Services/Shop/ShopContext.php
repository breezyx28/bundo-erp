<?php

namespace App\Services\Shop;

use App\Models\Tenant;

class ShopContext
{
    protected ?Tenant $tenant = null;

    /** @var array<string, mixed> */
    protected array $settings = [];

    public function setTenant(Tenant $tenant, array $settings): void
    {
        $this->tenant = $tenant;
        $this->settings = $settings;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return $this->settings;
    }
}
