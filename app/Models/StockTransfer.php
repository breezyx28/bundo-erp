<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Services\Branch\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use BelongsToTenant;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'from_branch_id', 'to_branch_id', 'number', 'status', 'notes',
        'requested_by', 'approved_by', 'dispatched_by', 'received_by',
        'approved_at', 'dispatched_at', 'received_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    /** @return HasMany<StockTransferItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    /** Only transfers touching a branch the user may access. */
    public function scopeVisible(Builder $query): Builder
    {
        $allowed = app(BranchContext::class)->allowedBranchIds()->all();

        return $query->where(function (Builder $q) use ($allowed) {
            $q->whereIn('from_branch_id', $allowed ?: [0])
                ->orWhereIn('to_branch_id', $allowed ?: [0]);
        });
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_REQUESTED, self::STATUS_APPROVED], true);
    }
}
