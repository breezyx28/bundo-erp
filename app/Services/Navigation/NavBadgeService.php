<?php

namespace App\Services\Navigation;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Illuminate\Support\Facades\Cache;

/**
 * Numeric alert counts shown as badges on sidebar nav links.
 *
 * All reads go through branch-scoped models, so counts respect the user's active
 * branch (or the consolidated view). Results are cached briefly per user+branch
 * to keep them off the hot path of every navigation.
 */
class NavBadgeService
{
    public function __construct(protected BranchContext $context) {}

    /**
     * Badge data keyed by nav route name.
     *
     * @return array<string, array{count:int, tone:string}>
     */
    public function badges(User $user): array
    {
        $branchKey = $this->context->currentBranchId() ?? 'all';

        return Cache::remember("nav-badges:{$user->id}:{$branchKey}", now()->addSeconds(60), function () use ($user) {
            $overdue = $this->overdueInvoices();
            $lowStock = $this->lowStockCount();
            $unpaidPurchases = $this->unpaidPurchaseOrders();
            $pendingShipments = $this->pendingShipments();
            $unread = $user->unreadNotifications()->count();

            return array_filter([
                'debts.index' => $this->badge($overdue, 'error'),
                'purchases.index' => $this->badge($unpaidPurchases, 'warning'),
                'inventory.index' => $this->badge($lowStock, 'warning'),
                'notifications.index' => $this->badge($unread, 'primary'),
                'shipments.index' => $this->badge($pendingShipments, 'warning'),
            ]);
        });
    }

    /**
     * @return array{count:int, tone:string}|null
     */
    protected function badge(int $count, string $tone): ?array
    {
        return $count > 0 ? ['count' => $count, 'tone' => $tone] : null;
    }

    protected function overdueInvoices(): int
    {
        return SalesInvoice::query()
            ->outstanding()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();
    }

    protected function unpaidPurchaseOrders(): int
    {
        return PurchaseOrder::query()->outstandingPayables()->count();
    }

    protected function pendingShipments(): int
    {
        return Shipment::query()
            ->whereNotIn('status', [Shipment::STATUS_DELIVERED, Shipment::STATUS_RETURNED])
            ->count();
    }

    protected function lowStockCount(): int
    {
        $stock = ProductBatch::query()
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        return Product::query()
            ->active()
            ->where('reorder_level', '>', 0)
            ->get(['id', 'reorder_level'])
            ->filter(fn (Product $p) => (int) ($stock[$p->id] ?? 0) < (int) $p->reorder_level)
            ->count();
    }
}
