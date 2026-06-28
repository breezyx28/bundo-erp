<?php

namespace App\Services\DataTransfer;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Supplier;

/**
 * Builds heading + data rows for every exportable dataset. All reads go through
 * the scoped Eloquent models, so exports respect the active branch (transactional
 * data) and tenant (master data) just like the rest of the app.
 */
class ExportService
{
    public const TYPES = ['products', 'customers', 'suppliers', 'sales', 'expenses'];

    /**
     * @return array{headings:list<string>, rows:list<array<int, scalar|null>>}
     */
    public function build(string $type): array
    {
        return match ($type) {
            'products' => $this->products(),
            'customers' => $this->customers(),
            'suppliers' => $this->suppliers(),
            'sales' => $this->sales(),
            'expenses' => $this->expenses(),
            default => ['headings' => [], 'rows' => []],
        };
    }

    /**
     * @return array{headings:list<string>, rows:list<array<int, scalar|null>>}
     */
    protected function products(): array
    {
        $rows = [];
        Product::query()->with(['category:id,name', 'brand:id,name'])
            ->orderBy('name')
            ->chunk(500, function ($chunk) use (&$rows) {
                foreach ($chunk as $p) {
                    $rows[] = [
                        $p->name, $p->sku, $p->barcode,
                        $p->category?->name, $p->brand?->name, $p->unit,
                        (float) $p->cost_price, (float) $p->selling_price,
                        (int) $p->reorder_level, $p->is_active ? 1 : 0,
                    ];
                }
            });

        return [
            'headings' => ['name', 'sku', 'barcode', 'category', 'brand', 'unit', 'cost_price', 'selling_price', 'reorder_level', 'is_active'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{headings:list<string>, rows:list<array<int, scalar|null>>}
     */
    protected function customers(): array
    {
        $rows = [];
        Customer::query()->orderBy('name')->chunk(500, function ($chunk) use (&$rows) {
            foreach ($chunk as $c) {
                $rows[] = [
                    $c->name, $c->phone, $c->email, $c->address, $c->type,
                    (float) $c->credit_limit, (float) $c->opening_balance, $c->is_active ? 1 : 0,
                ];
            }
        });

        return [
            'headings' => ['name', 'phone', 'email', 'address', 'type', 'credit_limit', 'opening_balance', 'is_active'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{headings:list<string>, rows:list<array<int, scalar|null>>}
     */
    protected function suppliers(): array
    {
        $rows = [];
        Supplier::query()->orderBy('name')->chunk(500, function ($chunk) use (&$rows) {
            foreach ($chunk as $s) {
                $rows[] = [
                    $s->name, $s->code, $s->contact_person, $s->phone, $s->email,
                    $s->address, $s->tax_number, (float) $s->opening_balance, $s->is_active ? 1 : 0,
                ];
            }
        });

        return [
            'headings' => ['name', 'code', 'contact_person', 'phone', 'email', 'address', 'tax_number', 'opening_balance', 'is_active'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{headings:list<string>, rows:list<array<int, scalar|null>>}
     */
    protected function sales(): array
    {
        $rows = [];
        SalesInvoice::query()->with('customer:id,name')
            ->orderByDesc('invoice_date')
            ->chunk(500, function ($chunk) use (&$rows) {
                foreach ($chunk as $i) {
                    $rows[] = [
                        $i->invoice_number,
                        $i->invoice_date->toDateString(),
                        $i->customer?->name,
                        $i->sale_type,
                        (float) $i->net_amount,
                        (float) $i->paid_amount,
                        (float) $i->balance,
                        $i->payment_status,
                    ];
                }
            });

        return [
            'headings' => ['invoice_number', 'invoice_date', 'customer', 'sale_type', 'net_amount', 'paid_amount', 'balance', 'payment_status'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{headings:list<string>, rows:list<array<int, scalar|null>>}
     */
    protected function expenses(): array
    {
        $rows = [];
        Expense::query()->with('category:id,name')
            ->orderByDesc('expense_date')
            ->chunk(500, function ($chunk) use (&$rows) {
                foreach ($chunk as $e) {
                    $rows[] = [
                        $e->expense_date->toDateString(),
                        $e->category?->name,
                        (float) $e->amount,
                        $e->description,
                        $e->payment_method,
                        $e->receipt_number,
                    ];
                }
            });

        return [
            'headings' => ['expense_date', 'category', 'amount', 'description', 'payment_method', 'receipt_number'],
            'rows' => $rows,
        ];
    }
}
