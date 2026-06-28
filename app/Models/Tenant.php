<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'domain', 'database_name', 'logo',
        'primary_color', 'secondary_color', 'is_active', 'settings', 'onboarding_completed_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'onboarding_completed_at' => 'datetime',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function needsOnboarding(): bool
    {
        return $this->onboarding_completed_at === null;
    }

    public function modules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }
}
