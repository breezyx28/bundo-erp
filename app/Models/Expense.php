<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Expense extends Model
{
    use BelongsToBranch, HasFactory, LogsActivity, SoftDeletes;

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK = 'bank_transfer';

    public const METHOD_CHECK = 'check';

    protected $fillable = [
        'tenant_id', 'branch_id', 'expense_category_id', 'reference_id', 'reference_type',
        'amount', 'amount_usd', 'description', 'expense_date', 'payment_method',
        'receipt_number', 'receipt_image', 'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'expense_category_id', 'description', 'expense_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<ExpenseCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isLinked(): bool
    {
        return $this->reference_type !== null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where('description', 'like', "%{$term}%")
            ->orWhere('receipt_number', 'like', "%{$term}%");
    }
}
