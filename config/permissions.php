<?php

use App\Enums\UserRole;

return [
    UserRole::SuperAdmin->value => [
        'tenants.manage',
        'tenants.suspend',
        'users.impersonate',
        'audit.view_all',
        'audit.dashboard',
        'platform.settings',
    ],
    UserRole::Admin->value => [
        'tenant.update',
        'users.manage',
        'users.invite',
        'users.remove',
        'roles.view',
        'roles.manage',
        'features.override',
        'usage.report',
        'usage.view',
        'data.export',
        'data.import',
        'data.migrate',
        'data.delete',
        'integrations.manage',
        'audit.view',
        'compliance.gdpr',
    ],
    UserRole::Member->value => [
        'usage.view',
        'audit.view',
        'compliance.gdpr',
    ],
];
