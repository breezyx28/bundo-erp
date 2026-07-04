<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasCustomFields;
use App\Services\Branch\BranchContext;
use App\Support\FormSelectCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use BelongsToTenant, HasCustomFields, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'phone', 'email', 'address', 'type',
        'credit_limit', 'opening_balance', 'notes', 'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => app(FormSelectCatalog::class)->flush('customers'));
        static::deleted(fn () => app(FormSelectCatalog::class)->flush('customers'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone', 'type', 'credit_limit', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function branchBalances(): HasMany
    {
        return $this->hasMany(CustomerBranchBalance::class);
    }

    /** Outstanding balance in the active branch (or consolidated across allowed branches). */
    public function currentBalance(): float
    {
        $context = app(BranchContext::class);
        $branchId = $context->currentBranchId();

        $query = $this->branchBalances();

        if ($branchId !== null) {
            return (float) ($query->where('branch_id', $branchId)->value('balance') ?? 0);
        }

        return (float) $query->whereIn('branch_id', $context->allowedBranchIds())->sum('balance');
    }

    /**
     * Visual status badges for list/detail views.
     *
     * @return array<int, array{label:string, color:string}>
     */
    public function badges(): array
    {
        $badges = [];

        if ($this->type === 'wholesale') {
            $badges[] = ['label' => 'wholesale', 'color' => 'badge-info'];
        }

        if ($this->created_at && $this->created_at->gt(now()->subDays(30))) {
            $badges[] = ['label' => 'new', 'color' => 'badge-success'];
        }

        $balance = $this->currentBalance();

        if ($this->credit_limit > 0 && $balance > (float) $this->credit_limit) {
            $badges[] = ['label' => 'over_limit', 'color' => 'badge-error'];
        } elseif ($balance > 0) {
            $badges[] = ['label' => 'has_debt', 'color' => 'badge-warning'];
        }

        return $badges;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%"));
    }
}
