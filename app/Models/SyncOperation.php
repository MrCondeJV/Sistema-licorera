<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncOperation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sync_token',
        'operation_type',
        'payload',
        'status',
        'result_reference_type',
        'result_reference_id',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
