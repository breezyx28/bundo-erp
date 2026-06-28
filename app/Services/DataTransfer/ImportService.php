<?php

namespace App\Services\DataTransfer;

use App\Imports\RowsImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ImportLog;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Branch\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Validates and persists bulk uploads (CSV / XLSX). Each row is validated in
 * isolation: valid rows are written, invalid rows are collected with a precise
 * reason, and the whole run is recorded as an ImportLog for auditing. Master
 * data imports are tenant-scoped; expense imports are assigned to a branch.
 */
class ImportService
{
    public const TYPES = ['products', 'customers', 'suppliers', 'expenses'];

    public function __construct(protected BranchContext $context) {}

    /**
     * @return list<string> Column headings expected for a given import type (used for the template).
     */
    public function template(string $type): array
    {
        return array_keys($this->rules($type));
    }

    /**
     * Run an import from an uploaded file path.
     */
    public function run(string $type, string $path, ?int $branchId = null): ImportLog
    {
        $sheets = Excel::toArray(new RowsImport, $path);
        /** @var list<array<string, mixed>> $rows */
        $rows = $sheets[0] ?? [];

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($type, $rows, $branchId, &$imported, &$errors) {
            foreach ($rows as $index => $row) {
                $line = $index + 2; // account for the heading row + 1-based numbering

                $validator = Validator::make($row, $this->rules($type));
                if ($validator->fails()) {
                    $errors[] = ['row' => $line, 'messages' => $validator->errors()->all()];

                    continue;
                }

                $this->persist($type, $validator->validated(), $branchId);
                $imported++;
            }
        });

        $failed = count($errors);

        return ImportLog::create([
            'branch_id' => $branchId,
            'user_id' => Auth::id(),
            'type' => $type,
            'file_name' => basename($path),
            'total_rows' => count($rows),
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'status' => $failed > 0 && $imported === 0 ? ImportLog::STATUS_FAILED : ImportLog::STATUS_COMPLETED,
            'errors' => $errors !== [] ? $errors : null,
        ]);
    }

    /**
     * Validation rules per type. Keys double as the expected column headings.
     *
     * @return array<string, string>
     */
    protected function rules(string $type): array
    {
        return match ($type) {
            'products' => [
                'name' => 'required|string|max:255',
                'sku' => 'nullable|string|max:100',
                'barcode' => 'nullable|string|max:100',
                'category' => 'nullable|string|max:255',
                'brand' => 'nullable|string|max:255',
                'unit' => 'nullable|string|max:50',
                'cost_price' => 'nullable|numeric|min:0',
                'selling_price' => 'nullable|numeric|min:0',
                'reorder_level' => 'nullable|integer|min:0',
            ],
            'customers' => [
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:500',
                'type' => 'nullable|in:retail,wholesale',
                'credit_limit' => 'nullable|numeric|min:0',
                'opening_balance' => 'nullable|numeric',
            ],
            'suppliers' => [
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:100',
                'contact_person' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:500',
                'tax_number' => 'nullable|string|max:100',
                'opening_balance' => 'nullable|numeric',
            ],
            'expenses' => [
                'expense_date' => 'required|date',
                'category' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:500',
                'payment_method' => 'nullable|in:cash,bank_transfer,check',
                'receipt_number' => 'nullable|string|max:100',
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function persist(string $type, array $row, ?int $branchId): void
    {
        match ($type) {
            'products' => $this->persistProduct($row),
            'customers' => $this->persistCustomer($row),
            'suppliers' => $this->persistSupplier($row),
            'expenses' => $this->persistExpense($row, $branchId),
            default => null,
        };
    }

    /** @param array<string, mixed> $row */
    protected function persistProduct(array $row): void
    {
        $categoryId = ! empty($row['category'])
            ? Category::firstOrCreate(['name' => (string) $row['category']])->id
            : null;
        $brandId = ! empty($row['brand'])
            ? Brand::firstOrCreate(['name' => (string) $row['brand']])->id
            : null;

        Product::updateOrCreate(
            ['sku' => $row['sku'] ?? null, 'name' => (string) $row['name']],
            [
                'barcode' => $row['barcode'] ?? null,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'unit' => $row['unit'] ?? 'pair',
                'cost_price' => (float) ($row['cost_price'] ?? 0),
                'selling_price' => (float) ($row['selling_price'] ?? 0),
                'reorder_level' => (int) ($row['reorder_level'] ?? 0),
                'is_active' => true,
            ],
        );
    }

    /** @param array<string, mixed> $row */
    protected function persistCustomer(array $row): void
    {
        Customer::updateOrCreate(
            ['name' => (string) $row['name'], 'phone' => $row['phone'] ?? null],
            [
                'email' => $row['email'] ?? null,
                'address' => $row['address'] ?? null,
                'type' => $row['type'] ?? 'retail',
                'credit_limit' => (float) ($row['credit_limit'] ?? 0),
                'opening_balance' => (float) ($row['opening_balance'] ?? 0),
                'is_active' => true,
            ],
        );
    }

    /** @param array<string, mixed> $row */
    protected function persistSupplier(array $row): void
    {
        Supplier::updateOrCreate(
            ['name' => (string) $row['name']],
            [
                'code' => $row['code'] ?? null,
                'contact_person' => $row['contact_person'] ?? null,
                'phone' => $row['phone'] ?? null,
                'email' => $row['email'] ?? null,
                'address' => $row['address'] ?? null,
                'tax_number' => $row['tax_number'] ?? null,
                'opening_balance' => (float) ($row['opening_balance'] ?? 0),
                'is_active' => true,
            ],
        );
    }

    /** @param array<string, mixed> $row */
    protected function persistExpense(array $row, ?int $branchId): void
    {
        $branchId = $branchId ?? $this->context->currentBranchId();
        $category = ExpenseCategory::firstOrCreate(['name' => (string) $row['category']]);

        Expense::create([
            'tenant_id' => $this->context->currentTenantId(),
            'branch_id' => $branchId,
            'expense_category_id' => $category->id,
            'amount' => (float) $row['amount'],
            'description' => $row['description'] ?? null,
            'expense_date' => $row['expense_date'],
            'payment_method' => $row['payment_method'] ?? Expense::METHOD_CASH,
            'receipt_number' => $row['receipt_number'] ?? null,
            'recorded_by' => Auth::id(),
        ]);
    }
}
