<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'key', 'name', 'description', 'icon', 'is_core', 'default_enabled', 'sort_order',
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'default_enabled' => 'boolean',
    ];

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }
}
