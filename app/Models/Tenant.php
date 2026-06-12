<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'description',
        'settings',
        'status',
        'owner_id',
        'activated_at',
    ];

    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }

    protected $casts = [
        'settings' => 'json',
        'activated_at' => 'datetime',
    ];

        public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

        public function users()
    {
        return $this->hasMany(User::class);
    }

        public function isActive(): bool
    {
        return $this->status === 'active';
    }

        public function activate()
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

        public function deactivate()
    {
        $this->update(['status' => 'inactive']);
    }
}
