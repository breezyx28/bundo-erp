<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use BelongsToTenant;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'branch_id', 'user_id', 'type', 'file_name',
        'total_rows', 'imported_rows', 'failed_rows', 'status', 'errors',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'imported_rows' => 'integer',
        'failed_rows' => 'integer',
        'errors' => 'array',
    ];

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
