<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseOrder extends Model
{
    use BelongsToBranch, LogsActivity, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    public const PAY_UNPAID = 'unpaid';

    public const PAY_PARTIAL = 'partial';

    public const PAY_PAID = 'paid';

    protected $fillable = [
        'tenant_id', 'branch_id', 'supplier_id', 'po_number', 'order_date',
        'expected_delivery_date', 'payment_due_date', 'total_amount', 'total_amount_usd', 'paid_amount',
        'payment_status', 'order_status', 'notes', 'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'payment_due_date' => 'date',
        'total_amount' => 'decimal:2',
        'total_amount_usd' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['po_number', 'order_status', 'payment_status', 'total_amount', 'paid_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<PurchaseOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function outstanding(): float
    {
        return round((float) $this->total_amount - (float) $this->paid_amount, 2);
    }

    /** Purchase orders that still owe the supplier money (excluding cancelled). */
    public function scopeOutstandingPayables(Builder $query): Builder
    {
        return $query->whereColumn('paid_amount', '<', 'total_amount')
            ->where('order_status', '!=', self::STATUS_CANCELLED);
    }

    /** Whole days past the payment due date; 0 if not yet due or no due date. */
    public function paymentDaysOverdue(): int
    {
        if (! $this->payment_due_date) {
            return 0;
        }

        return max(0, (int) $this->payment_due_date->startOfDay()->diffInDays(now()->startOfDay(), false));
    }

    /** Aging bucket key for supplier payables: current | d30 | d60 | d90. */
    public function paymentAgingBucket(): string
    {
        $days = $this->paymentDaysOverdue();

        return match (true) {
            $days <= 30 => 'current',
            $days <= 60 => 'd30',
            $days <= 90 => 'd60',
            default => 'd90',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this->order_status, [self::STATUS_DRAFT, self::STATUS_ORDERED], true);
    }

    public function isReceivable(): bool
    {
        return in_array($this->order_status, [self::STATUS_ORDERED, self::STATUS_PARTIAL], true);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('order_status', $status) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('po_number', 'like', "%{$term}%")
                ->orWhereHas('supplier', fn (Builder $s) => $s->where('name', 'like', "%{$term}%"));
        });
    }
}
