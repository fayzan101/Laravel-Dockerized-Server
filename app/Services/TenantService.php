<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TenantService
{
    public static function current(): ?Tenant
    {
        if (!Auth::check() || !Auth::user()->tenant_id) {
            return null;
        }

        return Auth::user()->tenant;
    }

        public static function id(): ?int
    {
        return Auth::check() ? Auth::user()->tenant_id : null;
    }

        public static function isAdmin(): bool
    {
                $user = Auth::user();
        return $user && $user->isAdmin();
    }

        public static function findBySlug(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)->first();
    }

        public static function scope()
    {
        return ['tenant_id' => self::id()];
    }
}
