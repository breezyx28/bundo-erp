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

class Shipment extends Model
{
    use BelongsToBranch, LogsActivity, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_HANDED = 'handed_to_logistics';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_ARRIVED = 'arrived';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_RETURNED = 'returned';

    public const MODE_PER_INVOICE = 'per_invoice';

    public const MODE_GLOBAL = 'global';

    /**
     * Forward-only status machine. Each status maps to the statuses it may advance to.
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PROCESSING],
        self::STATUS_PROCESSING => [self::STATUS_HANDED],
        self::STATUS_HANDED => [self::STATUS_IN_TRANSIT],
        self::STATUS_IN_TRANSIT => [self::STATUS_ARRIVED],
        self::STATUS_ARRIVED => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED => [],
        self::STATUS_RETURNED => [],
    ];

    protected $fillable = [
        'tenant_id', 'branch_id', 'sales_invoice_id', 'customer_id', 'logistics_company_id',
        'tracking_number', 'waybill_number', 'dispatch_city', 'delivery_city', 'number_of_boxes',
        'shipment_value', 'shipping_cost', 'shipping_cost_usd', 'cost_mode', 'status',
        'pod_image', 'notes', 'dispatched_at', 'delivered_at', 'created_by',
    ];

    protected $casts = [
        'number_of_boxes' => 'integer',
        'shipment_value' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'shipping_cost_usd' => 'decimal:2',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'shipping_cost', 'tracking_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<LogisticsCompany, $this> */
    public function logisticsCompany(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class);
    }

    /** @return HasMany<ShipmentReturn, $this> */
    public function returns(): HasMany
    {
        return $this->hasMany(ShipmentReturn::class);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function nextStatus(): ?string
    {
        return self::TRANSITIONS[$this->status][0] ?? null;
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_RETURNED], true);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where('tracking_number', 'like', "%{$term}%")
            ->orWhere('waybill_number', 'like', "%{$term}%")
            ->orWhere('delivery_city', 'like', "%{$term}%");
    }
}
