<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];

        public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

        public function users()
    {
        return $this->belongsToMany(User::class);
    }

        public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}
