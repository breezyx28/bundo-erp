<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'product_id', 'variant_id', 'quantity', 'received_quantity',
        'cost_per_unit', 'cost_per_unit_usd', 'total', 'total_usd',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'cost_per_unit' => 'decimal:2',
        'cost_per_unit_usd' => 'decimal:2',
        'total' => 'decimal:2',
        'total_usd' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function outstandingQuantity(): int
    {
        return max(0, $this->quantity - $this->received_quantity);
    }
}
