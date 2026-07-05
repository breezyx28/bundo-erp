<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\LogisticsCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Distinct previously-entered values for free-text inputs (autocomplete).
 * Fields are whitelisted; all models are tenant-scoped via BelongsToTenant.
 */
class SuggestionController extends Controller
{
    /** @var array<string, array{0: class-string, 1: string}> */
    protected const FIELDS = [
        'expense_description' => [Expense::class, 'description'],
        'logistics_contact_person' => [LogisticsCompany::class, 'contact_person'],
        'customer_address' => [Customer::class, 'address'],
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $field = (string) $request->string('field');
        $term = (string) $request->string('q');

        if (! isset(self::FIELDS[$field])) {
            return response()->json([]);
        }

        [$model, $column] = self::FIELDS[$field];

        $values = $model::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->when($term, fn ($q) => $q->where($column, 'like', "%{$term}%"))
            ->distinct()
            ->orderBy($column)
            ->limit(10)
            ->pluck($column)
            ->all();

        return response()->json($values);
    }
}
