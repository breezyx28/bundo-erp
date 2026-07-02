<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Models\StockTransfer;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\StockTransferService;
use App\Support\FormSelectCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransferController extends Controller
{
    use InteractsWithToast;

    public function index(Request $request, BranchContext $context, FormSelectCatalog $catalog): Response
    {
        $statusFilter = (string) $request->string('status');

        return Inertia::render('Transfers/Index', [
            'transfers' => StockTransfer::query()
                ->visible()
                ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
                ->with(['fromBranch:id,name', 'toBranch:id,name'])
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (StockTransfer $tr) => [
                    'id' => $tr->id,
                    'number' => $tr->number,
                    'from' => $tr->fromBranch?->name,
                    'to' => $tr->toBranch?->name,
                    'status' => $tr->status,
                    'created' => $tr->created_at?->format('Y-m-d'),
                ]),
            'branchOptions' => $context->allowedBranches()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
            'defaultFromBranch' => $context->currentBranchId() ?? $context->allowedBranchIds()->first(),
            'productOptions' => $catalog->products(),
            'statusOptions' => collect(['requested', 'approved', 'dispatched', 'received', 'cancelled'])
                ->map(fn ($s) => ['value' => $s, 'label' => __('inventory.status.'.$s)])->all(),
            'detail' => Inertia::optional(fn () => $this->detailData($request->integer('detail'))),
            'filters' => [
                'status' => $statusFilter ?: null,
            ],
            'canManage' => Gate::allows('inventory.transfer'),
        ]);
    }

    public function store(Request $request, StockTransferService $service): RedirectResponse
    {
        Gate::authorize('inventory.transfer');

        $data = $request->validate([
            'from_branch_id' => 'required|integer|different:to_branch_id',
            'to_branch_id' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $service->request(
                fromBranchId: $data['from_branch_id'],
                toBranchId: $data['to_branch_id'],
                items: $data['items'],
                notes: ($data['notes'] ?? null) ?: null,
            );
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return redirect()->back();
        }

        $this->toastSuccess(__('inventory.transfer_created'));

        return redirect()->route('transfers.index');
    }

    public function action(Request $request, StockTransfer $transfer, StockTransferService $service): RedirectResponse
    {
        Gate::authorize('inventory.transfer');

        $action = (string) $request->string('action');

        try {
            match ($action) {
                'approve' => $service->approve($transfer),
                'dispatch' => $service->dispatch($transfer),
                'receive' => $service->receive($transfer),
                'cancel' => $service->cancel($transfer),
                default => throw new \InvalidArgumentException('Unknown action.'),
            };

            $this->toastSuccess(__('inventory.transfer_'.$action.'d'));
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());
        }

        return redirect()->route('transfers.index');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function detailData(?int $id): ?array
    {
        if (! $id) {
            return null;
        }

        $tr = StockTransfer::query()->visible()
            ->with(['items.product:id,name', 'items.variant', 'fromBranch:id,name', 'toBranch:id,name'])
            ->find($id);

        if (! $tr) {
            return null;
        }

        return [
            'number' => $tr->number,
            'status' => $tr->status,
            'from' => $tr->fromBranch?->name,
            'to' => $tr->toBranch?->name,
            'notes' => $tr->notes,
            'items' => $tr->items->map(fn ($i) => [
                'product' => $i->product?->name,
                'variant' => $i->variant?->label(),
                'quantity' => $i->quantity,
                'received_quantity' => $i->received_quantity,
            ])->all(),
        ];
    }
}
