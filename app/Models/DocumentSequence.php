<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'tenant_id', 'branch_id', 'type', 'prefix', 'next_number', 'padding',
    ];

    protected $casts = [
        'next_number' => 'integer',
        'padding' => 'integer',
    ];
}
