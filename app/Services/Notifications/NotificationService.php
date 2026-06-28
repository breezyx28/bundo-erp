<?php

namespace App\Services\Notifications;

use App\Models\Branch;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\Scopes\BranchScope;
use App\Models\Scopes\TenantScope;
use App\Models\Shipment;
use App\Models\StockTransfer;
use App\Models\User;
use App\Notifications\SystemAlert;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Central place that turns domain events into user notifications. Recipients are
 * the active users tied to the relevant branch. Scans (low stock, overdue debt)
 * are tenant-explicit so they can run from the scheduler without an auth context.
 */
class NotificationService
{
    /**
     * Active users attached to a branch (membership or default branch).
     *
     * @return Collection<int, User>
     */
    public function recipients(int $tenantId, int $branchId): Collection
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function (Builder $q) use ($branchId) {
                $q->whereHas('branches', fn (Builder $b) => $b->where('branches.id', $branchId))
                    ->orWhere('default_branch_id', $branchId);
            })
            ->get();
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public function send(Collection $users, SystemAlert $alert): void
    {
        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, $alert);
    }

    public function notifyBranch(int $tenantId, int $branchId, SystemAlert $alert): void
    {
        $this->send($this->recipients($tenantId, $branchId), $alert);
    }

    // ---- Event triggers -------------------------------------------------

    public function paymentReceived(Payment $payment): void
    {
        if ($payment->direction !== Payment::DIRECTION_IN) {
            return;
        }

        $this->notifyBranch((int) $payment->tenant_id, (int) $payment->branch_id, new SystemAlert(
            level: SystemAlert::LEVEL_SUCCESS,
            title: __('notifications.payment_received'),
            message: __('notifications.payment_received_body', ['amount' => Money::format((float) $payment->amount)]),
            url: route('debts.index', absolute: false),
            icon: 'o-banknotes',
            branchId: (int) $payment->branch_id,
        ));
    }

    public function shipmentUpdated(Shipment $shipment): void
    {
        $this->notifyBranch((int) $shipment->tenant_id, (int) $shipment->branch_id, new SystemAlert(
            level: SystemAlert::LEVEL_INFO,
            title: __('notifications.shipment_updated'),
            message: __('notifications.shipment_updated_body', [
                'number' => (string) $shipment->tracking_number,
                'status' => __('shipping.status.'.$shipment->status),
            ]),
            url: route('shipments.index', absolute: false),
            icon: 'o-paper-airplane',
            branchId: (int) $shipment->branch_id,
        ));
    }

    public function purchaseOrderCreated(PurchaseOrder $po): void
    {
        $this->notifyBranch((int) $po->tenant_id, (int) $po->branch_id, new SystemAlert(
            level: SystemAlert::LEVEL_INFO,
            title: __('notifications.new_po'),
            message: __('notifications.new_po_body', ['number' => (string) $po->po_number]),
            url: route('purchases.index', absolute: false),
            icon: 'o-shopping-bag',
            branchId: (int) $po->branch_id,
        ));
    }

    public function transferRequested(StockTransfer $transfer): void
    {
        $this->notifyBranch((int) $transfer->tenant_id, (int) $transfer->to_branch_id, new SystemAlert(
            level: SystemAlert::LEVEL_REMINDER,
            title: __('notifications.transfer_requested'),
            message: __('notifications.transfer_requested_body', ['number' => (string) $transfer->number]),
            url: route('transfers.index', absolute: false),
            icon: 'o-arrows-right-left',
            branchId: (int) $transfer->to_branch_id,
        ));
    }

    // ---- Scheduled scans ------------------------------------------------

    /**
     * Emit a low-stock alert per branch for the given tenant.
     *
     * @return int number of branches alerted
     */
    public function scanLowStock(int $tenantId): int
    {
        $products = Product::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->get(['id', 'reorder_level']);

        if ($products->isEmpty()) {
            return 0;
        }

        $alerted = 0;

        foreach ($this->tenantBranches($tenantId) as $branch) {
            $stock = ProductBatch::query()->withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $branch->id)
                ->selectRaw('product_id, SUM(quantity) as qty')
                ->groupBy('product_id')
                ->pluck('qty', 'product_id');

            $low = $products->filter(
                fn (Product $p) => (int) ($stock[$p->id] ?? 0) < (int) $p->reorder_level
            )->count();

            if ($low === 0) {
                continue;
            }

            $this->notifyBranch($tenantId, (int) $branch->id, new SystemAlert(
                level: SystemAlert::LEVEL_ALERT,
                title: __('notifications.low_stock'),
                message: __('notifications.low_stock_body', ['count' => $low, 'branch' => $branch->name]),
                url: route('inventory.index', absolute: false),
                icon: 'o-exclamation-triangle',
                branchId: (int) $branch->id,
            ));
            $alerted++;
        }

        return $alerted;
    }

    /**
     * Emit an overdue-receivables alert per branch for the given tenant.
     *
     * @return int number of branches alerted
     */
    public function scanOverdueDebts(int $tenantId): int
    {
        $alerted = 0;

        foreach ($this->tenantBranches($tenantId) as $branch) {
            $overdue = SalesInvoice::query()->withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $branch->id)
                ->where('sale_type', SalesInvoice::TYPE_CREDIT)
                ->where('balance', '>', 0)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count();

            if ($overdue === 0) {
                continue;
            }

            $this->notifyBranch($tenantId, (int) $branch->id, new SystemAlert(
                level: SystemAlert::LEVEL_ALERT,
                title: __('notifications.overdue_debt'),
                message: __('notifications.overdue_debt_body', ['count' => $overdue, 'branch' => $branch->name]),
                url: route('debts.index', absolute: false),
                icon: 'o-credit-card',
                branchId: (int) $branch->id,
            ));
            $alerted++;
        }

        return $alerted;
    }

    /**
     * @return Collection<int, Branch>
     */
    protected function tenantBranches(int $tenantId): Collection
    {
        return Branch::query()->where('tenant_id', $tenantId)->where('is_active', true)->get(['id', 'name']);
    }
}
