<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    protected $fillable = [
        'sales_invoice_id', 'product_id', 'variant_id', 'batch_id', 'quantity',
        'unit_price', 'unit_price_usd', 'cost_per_unit', 'discount_type', 'discount_value',
        'total', 'total_usd',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'unit_price_usd' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'total' => 'decimal:2',
        'total_usd' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }
}
