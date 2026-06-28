<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToBranch;

    public const DIRECTION_IN = 'in';   // received from a customer

    public const DIRECTION_OUT = 'out'; // paid to a supplier

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK = 'bank_transfer';

    public const METHOD_CHECK = 'check';

    public const METHOD_MOBILE = 'mobile_money';

    protected $fillable = [
        'tenant_id', 'branch_id', 'customer_id', 'supplier_id', 'sales_invoice_id',
        'purchase_order_id', 'direction', 'amount', 'amount_usd', 'payment_method',
        'transaction_number', 'reference_number', 'payment_date', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
