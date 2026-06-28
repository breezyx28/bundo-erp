<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id', 'product_id', 'variant_id', 'location_id', 'batch_number',
        'quantity', 'initial_quantity', 'unit_cost', 'received_at', 'expiry_date',
        'source_type', 'source_id', 'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'initial_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'received_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    /** Batches still holding stock, oldest first (FIFO). */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0)->orderBy('received_at')->orderBy('id');
    }

    public function scopeForItem(Builder $query, int $productId, ?int $variantId): Builder
    {
        return $query->where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('variant_id', $variantId),
                fn ($q) => $q->whereNull('variant_id'));
    }
}
