<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Member = 'member';

    public static function tenantRoles(): array
    {
        return [self::Admin->value, self::Member->value];
    }
}
