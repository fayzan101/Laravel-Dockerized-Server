<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TenantService
{
    /**
     * Get the current tenant from authenticated user.
     */
    public static function current(): ?Tenant
    {
        if (!Auth::check() || !Auth::user()->tenant_id) {
            return null;
        }

        return Auth::user()->tenant;
    }

    /**
     * Get current tenant ID.
     */
    public static function id(): ?int
    {
        return Auth::check() ? Auth::user()->tenant_id : null;
    }

    /**
     * Check if current user is tenant admin.
     */
    public static function isAdmin(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return $user && $user->isAdmin();
    }

    /**
     * Find tenant by slug.
     */
    public static function findBySlug(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)->first();
    }

    /**
     * Query builder scoped to current tenant.
     * Usage: Post::scopedToTenant()->get();
     */
    public static function scope()
    {
        return ['tenant_id' => self::id()];
    }
}
