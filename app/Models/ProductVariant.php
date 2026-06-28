<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'product_id', 'sku', 'barcode', 'options',
        'cost_price', 'selling_price', 'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function label(): string
    {
        $parts = collect($this->options ?? [])->values()->implode(' / ');

        return $parts !== '' ? $parts : $this->sku;
    }
}
