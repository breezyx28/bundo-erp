<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PurchaseOrder;
use App\Services\Expenses\ExpenseService;
use App\Support\FormSelectCatalog;
use App\Support\Money;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Expenses')] class extends Component
{
    use ConfirmsDeletion, UiToast, WithFileUploads, WithPagination;

    public string $search = '';

    public ?int $categoryFilter = null;

    public string $from = '';

    public string $to = '';

    // Form
    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $expense_category_id = null;

    public float $amount = 0;

    public string $description = '';

    public string $expense_date = '';

    public string $payment_method = 'cash';

    public string $receipt_number = '';

    public $receipt; // uploaded file

    public bool $linked = false;

    public ?int $purchase_order_id = null;

    /** @var list<array{id:int,name:string}> */
    public array $poOptions = [];

    public bool $formCatalogsLoaded = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    public function updatedLinked(bool $value): void
    {
        if ($value) {
            $this->loadFormCatalogs();
        }
    }

    protected function loadFormCatalogs(): void
    {
        if ($this->formCatalogsLoaded) {
            return;
        }

        $this->poOptions = app(FormSelectCatalog::class)->purchaseOrders();
        $this->formCatalogsLoaded = true;
    }

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
        $this->expense_date = now()->toDateString();
    }

    public function canManage(): bool
    {
        return Gate::allows('expenses.create');
    }

    public function with(ExpenseService $service): array
    {
        return [
            'expenses' => Expense::query()
                ->search($this->search)
                ->when($this->categoryFilter, fn ($q) => $q->where('expense_category_id', $this->categoryFilter))
                ->whereBetween('expense_date', \App\Support\DateRange::bounds($this->from, $this->to))
                ->with(['category:id,name'])
                ->latest('expense_date')->latest('id')
                ->paginate(10),
            'categoryOptions' => ExpenseCategory::query()->active()->orderBy('name')->get(['id', 'name'])->all(),
            'methodOptions' => collect(['cash', 'bank_transfer', 'check'])
                ->map(fn ($m) => ['id' => $m, 'name' => __('purchasing.methods.' . $m)])->all(),
            'report' => $service->report($this->from, $this->to),
            'headers' => [
                ['key' => 'expense_date', 'label' => __('sales.date')],
                ['key' => 'category', 'label' => __('fields.category')],
                ['key' => 'description', 'label' => __('fields.description')],
                ['key' => 'amount', 'label' => __('purchasing.amount'), 'class' => 'text-end'],
            ],
        ];
    }

    public function money($amount): string
    {
        return Money::format($amount);
    }

    public function create(): void
    {
        $this->reset(['editingId', 'description', 'receipt_number', 'receipt', 'linked', 'purchase_order_id', 'expense_category_id']);
        $this->amount = 0;
        $this->payment_method = 'cash';
        $this->expense_date = now()->toDateString();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $expense = Expense::findOrFail($id);
        $this->editingId = $expense->id;
        $this->expense_category_id = $expense->expense_category_id;
        $this->amount = (float) $expense->amount;
        $this->description = (string) $expense->description;
        $this->expense_date = $expense->expense_date->toDateString();
        $this->payment_method = $expense->payment_method;
        $this->receipt_number = (string) $expense->receipt_number;
        $this->linked = $expense->reference_type === PurchaseOrder::class;
        $this->purchase_order_id = $this->linked ? $expense->reference_id : null;
        $this->receipt = null;
        $this->loadFormCatalogs();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'expenses.update' : 'expenses.create');

        $data = $this->validate([
            'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:1000',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,check',
            'receipt_number' => 'nullable|string|max:100',
            'receipt' => 'nullable|image|max:4096',
            'linked' => 'boolean',
            'purchase_order_id' => 'nullable|required_if:linked,true|integer|exists:purchase_orders,id',
        ]);

        $expense = $this->editingId ? Expense::findOrFail($this->editingId) : new Expense;
        $expense->fill([
            'tenant_id' => Auth::user()->tenant_id,
            'expense_category_id' => $data['expense_category_id'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'],
            'receipt_number' => $data['receipt_number'] ?: null,
            'recorded_by' => Auth::id(),
            'reference_type' => $this->linked ? PurchaseOrder::class : null,
            'reference_id' => $this->linked ? $data['purchase_order_id'] : null,
        ]);

        if ($this->receipt) {
            $expense->receipt_image = $this->receipt->store('expenses', 'public');
        }

        $expense->save();

        $this->showForm = false;
        $this->success($this->editingId ? __('common.updated') : __('common.created'));
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $this->authorize('expenses.delete');
        Expense::findOrFail($this->deleteId)->delete();
        $this->cancelDelete();
        $this->warning(__('common.deleted'));
    }
}; ?>

<div class="space-y-6">
    <x-ui.header :title="__('nav.expenses')" separator progress-indicator>
        <x-slot:actions>
            <x-ui.button :label="__('nav.expense_categories')" icon="o-tag" link="{{ route('expense-categories.index') }}" class="btn-ghost btn-sm" />
            @if ($this->canManage())
                <x-ui.button :label="__('expenses.new')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    {{-- Report --}}
    <x-ui.card :title="__('expenses.report')">
        <x-slot:menu>
            <x-ui.input wire:model.live="from" type="date" class="w-40" />
            <x-ui.input wire:model.live="to" type="date" class="w-40" />
            <x-ui.select wire:model.live="categoryFilter" :options="$categoryOptions" option-value="id" option-label="name"
                :placeholder="__('common.all')" class="w-48" />
        </x-slot:menu>
        <div class="grid gap-4 lg:grid-cols-3">
            <x-ui.stats-group compact>
                <x-ui.stats-row single>
                    <x-ui.stat :title="__('expenses.total')" :value="$this->money($report['total'])"
                        :description="__('expenses.count', ['count' => $report['count']])" icon="o-banknotes" color="text-error" />
                </x-ui.stats-row>
            </x-ui.stats-group>
            <div class="lg:col-span-2">
                <div class="mb-2 text-sm font-medium">{{ __('expenses.by_category') }}</div>
                <div class="space-y-1">
                    @forelse ($report['by_category'] as $c)
                        <div class="flex items-center justify-between rounded-box border border-base-300 px-3 py-2 text-sm">
                            <span>{{ $c['category'] }} <span class="text-base-content/40">· {{ $c['count'] }}</span></span>
                            <span class="tabular-nums font-medium">{{ $this->money($c['total']) }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-base-content/50">{{ __('common.no_results') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$expenses" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_expense_date', $row)
                <span class="text-xs">{{ $row->expense_date?->format('Y-m-d') }}</span>
            @endscope
            @scope('cell_category', $row)
                {{ $row->category?->name }}
                @if ($row->isLinked())
                    <x-ui.badge :value="__('expenses.linked')" class="badge-info badge-sm ms-1" />
                @endif
            @endscope
            @scope('cell_amount', $row)
                <span class="text-end tabular-nums font-medium">{{ $this->money($row->amount) }}</span>
            @endscope
            @scope('actions', $row)
                @if ($this->canManage())
                    @if ($row->receipt_image)
                        <x-ui.button icon="o-paper-clip" link="{{ Storage::url($row->receipt_image) }}" external class="btn-text btn-circle btn-sm" />
                    @endif
                    <x-ui.button icon="o-pencil" wire:click.stop="edit({{ $row->id }})" class="btn-text btn-circle btn-sm" />
                    <x-ui.button icon="o-trash" wire:click.stop="confirmDelete({{ $row->id }})" class="btn-text btn-circle btn-sm text-error" />
                @endif
            @endscope
        </x-ui.table>
    </x-ui.card>

    {{-- Form --}}
    <x-ui.modal wire:model="showForm" :title="$editingId ? __('common.edit') : __('expenses.new')" separator box-class="max-w-xl">
        <div class="grid gap-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select :label="__('fields.category')" wire:model="expense_category_id" :options="$categoryOptions"
                    option-value="id" option-label="name" :placeholder="__('common.none')" />
                <x-ui.input :label="__('purchasing.amount')" wire:model="amount" type="number" step="0.01" min="0.01" />
            </div>
            <x-ui.textarea :label="__('fields.description')" wire:model="description" rows="2" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('sales.date')" wire:model="expense_date" type="date" />
                <x-ui.select :label="__('purchasing.method')" wire:model="payment_method" :options="$methodOptions"
                    option-value="id" option-label="name" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('expenses.receipt_number')" wire:model="receipt_number" />
                <x-ui.file :label="__('expenses.receipt')" wire:model="receipt" accept="image/*" />
            </div>

            <x-ui.toggle :label="__('expenses.link_to_po')" wire:model.live="linked" />
            @if ($linked)
                <x-form.search-select :label="__('nav.purchases')" wire:model="purchase_order_id" :options="$poOptions"
                    :placeholder="__('common.none')" />
            @endif
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showForm', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.delete-confirm-modal />
</div>
