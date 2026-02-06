<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
        protected static function bootBelongsToTenant()
    {
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->tenant_id) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::check() && Auth::user()->tenant_id) {
                $builder->where('tenant_id', Auth::user()->tenant_id);
            }
        });
    }

        public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
