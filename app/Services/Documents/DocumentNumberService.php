<?php

namespace App\Services\Documents;

use App\Models\DocumentSequence;
use App\Services\Branch\BranchContext;
use Illuminate\Support\Facades\DB;

/**
 * Generates gap-free, per-branch document numbers (invoices, POs, payments, ...).
 * Uses a row lock inside a transaction to guarantee uniqueness under concurrency.
 */
class DocumentNumberService
{
    /** Default prefixes per document type. */
    protected array $defaults = [
        'invoice' => 'INV-',
        'purchase_order' => 'PO-',
        'payment' => 'PAY-',
        'expense' => 'EXP-',
        'shipment' => 'SHP-',
        'transfer' => 'TRF-',
        'adjustment' => 'ADJ-',
    ];

    public function __construct(protected BranchContext $context) {}

    public function next(string $type, ?int $branchId = null): string
    {
        $tenantId = $this->context->currentTenantId();
        $branchId = $branchId ?? $this->context->currentBranchId();

        return DB::transaction(function () use ($type, $tenantId, $branchId) {
            $sequence = DocumentSequence::query()
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = DocumentSequence::create([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'type' => $type,
                    'prefix' => $this->defaults[$type] ?? strtoupper(substr($type, 0, 3)).'-',
                    'next_number' => 1,
                    'padding' => 5,
                ]);
            }

            $number = $sequence->next_number;
            $sequence->increment('next_number');

            return $sequence->prefix.str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT);
        });
    }
}
