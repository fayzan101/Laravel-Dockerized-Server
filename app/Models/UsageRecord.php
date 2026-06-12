<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageRecord extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'feature_key',
        'amount',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
        'recorded_at' => 'datetime',
    ];
}
