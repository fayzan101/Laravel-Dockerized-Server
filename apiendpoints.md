# API Documentation

Base URL: http://127.0.0.1:8000/api

## Common Headers

Content-Type: application/json

For protected endpoints:

Authorization: Bearer <access_token>

## Response Conventions

- Success responses return JSON objects or arrays.
- Errors return JSON with a message and an HTTP error status.

Example error:

```json
{
  "message": "Unauthorized"
}
```

## Observability & Health (Public)

### GET /health
Description: Simple health check.

Body: none

Response:
```json
{
  "status": "ok"
}
```

### GET /status
Description: Service status with DB connectivity.

Body: none

Response:
```json
{
  "status": "ok",
  "timestamp": "2026-02-06T10:00:00Z",
  "database": "ok"
}
```

### GET /metrics
Description: Global metrics.

Body: none

Response:
```json
{
  "tenants": {
    "total": 10,
    "active": 8,
    "inactive": 1,
    "suspended": 1
  },
  "users": {
    "total": 150
  },
  "timestamp": "2026-02-06T10:00:00Z"
}
```

## Authentication (Public)

### POST /auth/register
Description: Create a tenant and its admin user.

Body (JSON):
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "tenant_name": "Acme Corp",
  "tenant_slug": "acme-corp"
}
```

Response:
```json
{
  "message": "Account and tenant created successfully",
  "access_token": "<token>",
  "token_type": "Bearer",
  "user": {"id": 1},
  "tenant": {"id": 1}
}
```

### POST /auth/login
Description: Login and get an access token.

Body (JSON):
```json
{
  "email": "john@example.com",
  "password": "password123",
  "tenant_slug": "acme-corp"
}
```

Response:
```json
{
  "message": "Login successful",
  "access_token": "<token>",
  "token_type": "Bearer",
  "user": {"id": 1},
  "tenant": {"id": 1}
}
```

### POST /auth/forgot-password
Description: Send a password reset email.

Body (JSON):
```json
{
  "email": "john@example.com"
}
```

### POST /auth/reset-password
Description: Reset password with a token.

Body (JSON):
```json
{
  "token": "RESET_TOKEN_FROM_EMAIL",
  "email": "john@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### POST /auth/sso
Description: Stub endpoint for SSO.

Body: none

Response:
```json
{
  "message": "SSO is not implemented yet."
}
```

## Authentication (Protected)

### POST /auth/logout
Description: Revoke current access token.

Body: none

### POST /auth/refresh
Description: Revoke current token and issue a new one.

Body: none

Response:
```json
{
  "message": "Token refreshed successfully",
  "access_token": "<token>",
  "token_type": "Bearer"
}
```

## Tenant & User Management (Protected)

### GET /tenant/current
Description: Get current tenant details.

Body: none

### PUT /tenant/update
Description: Update tenant (admin only).

Body (JSON):
```json
{
  "name": "Acme Corp Updated",
  "slug": "acme-corp",
  "description": "Optional description",
  "settings": "{\"timezone\":\"UTC\"}"
}
```

### POST /tenant/invite-user
Description: Invite a user to tenant (admin only).

Body (JSON):
```json
{
  "email": "member@example.com",
  "role": "member"
}
```

### GET /tenant/users
Description: List users in the current tenant.

Body: none

### POST /tenant/remove-user
Description: Remove a user from tenant (admin only).

Body (JSON):
```json
{
  "user_id": 5
}
```

### POST /tenants/{tenantId}/users
Description: Create a user in a tenant (admin only).

Body (JSON):
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "role": "member",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### GET /tenants/{tenantId}/users
Description: List users in a tenant (admin only).

Body: none

### PUT /users/{userId}
Description: Update a user in the same tenant (admin only).

Body (JSON):
```json
{
  "name": "Jane Updated",
  "email": "jane2@example.com",
  "role": "admin",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### DELETE /users/{userId}
Description: Delete a user in the same tenant (admin only).

Body: none

### GET /user/profile
Description: Get the authenticated user profile.

Body: none

## Tenant Metrics (Protected)

### GET /tenants/{tenantId}/metrics
Description: Tenant-specific metrics (admin only).

Body: none

## IAM - Roles & Permissions (Protected)

### POST /roles
Description: Create a role in tenant (admin only).

Body (JSON):
```json
{
  "name": "manager",
  "description": "Manager role"
}
```

### GET /roles
Description: List roles in tenant.

Body: none

### POST /permissions
Description: Create a permission in tenant (admin only).

Body (JSON):
```json
{
  "name": "users.view",
  "description": "View users"
}
```

### GET /users/{userId}/permissions
Description: Get permissions via roles for a user (admin only).

Body: none

## Features & Usage (Protected)

### GET /features
Description: List global features.

Body: none

### GET /tenants/{tenantId}/features
Description: List effective features for a tenant.

Body: none

Response:
```json
[
  {
    "key": "projects",
    "name": "Projects",
    "description": "Number of projects",
    "enabled": true,
    "limit": 25
  }
]
```

### POST /tenants/{tenantId}/features/override
Description: Override feature settings for a tenant (admin only).

Body (JSON):
```json
{
  "feature_key": "projects",
  "enabled": true,
  "limit": 50
}
```

### GET /usage
Description: Usage summary for current tenant.

Query params (optional):
- feature_key
- from (ISO datetime)
- to (ISO datetime)

Response:
```json
{
  "tenant_id": 1,
  "summary": {
    "projects": 12,
    "api_calls": 340
  },
  "records_count": 7
}
```

### POST /usage/report
Description: Report usage for current tenant (admin only).

Body (JSON):
```json
{
  "feature_key": "api_calls",
  "amount": 5,
  "metadata": {"path": "/api/v1/items"},
  "recorded_at": "2026-02-06T10:00:00Z"
}
```
