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

    /**
     * Role belongs to a tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Users assigned to this role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Permissions attached to this role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}
