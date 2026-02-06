<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantFeatureOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'feature_key',
        'enabled',
        'limit',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'limit' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_key', 'key');
    }
}
