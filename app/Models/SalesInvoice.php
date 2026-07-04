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

class SalesInvoice extends Model
{
    use BelongsToBranch, LogsActivity, SoftDeletes;

    public const TYPE_CASH = 'cash';

    public const TYPE_CREDIT = 'credit';

    public const PAY_UNPAID = 'unpaid';

    public const PAY_PARTIAL = 'partial';

    public const PAY_PAID = 'paid';

    protected $fillable = [
        'tenant_id', 'branch_id', 'customer_id', 'invoice_number', 'invoice_date', 'due_date',
        'sale_type', 'total_amount', 'total_amount_usd', 'discount_type', 'discount_value',
        'discount_amount', 'net_amount', 'net_amount_usd', 'cost_total', 'paid_amount', 'balance',
        'payment_status', 'payment_method', 'transaction_number', 'exchange_rate', 'notes', 'created_by',
        'last_reminder_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'last_reminder_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'total_amount_usd' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'net_amount_usd' => 'decimal:2',
        'cost_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_number', 'net_amount', 'paid_amount', 'payment_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<SalesInvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function profit(): float
    {
        return round((float) $this->net_amount - (float) $this->cost_total, 2);
    }

    public function isOverdue(): bool
    {
        return $this->sale_type === self::TYPE_CREDIT
            && $this->payment_status !== self::PAY_PAID
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('payment_status', $status) : $query;
    }

    /** Invoices that still owe money (receivables). */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->where('balance', '>', 0);
    }

    /** Whole days past the due date (or invoice date when no due date); 0 if not yet due. */
    public function daysOverdue(): int
    {
        $reference = $this->due_date ?? $this->invoice_date;

        return max(0, (int) $reference->startOfDay()->diffInDays(now()->startOfDay(), false));
    }

    /** Aging bucket key: current | d30 | d60 | d90. */
    public function agingBucket(): string
    {
        $days = $this->daysOverdue();

        return match (true) {
            $days <= 30 => 'current',
            $days <= 60 => 'd30',
            $days <= 90 => 'd60',
            default => 'd90',
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('invoice_number', 'like', "%{$term}%")
                ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"));
        });
    }
}
