<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\CreateTenantUserRequest;
use App\Http\Requests\Tenant\InviteUserRequest;
use App\Http\Requests\Tenant\RemoveUserRequest;
use App\Http\Requests\Tenant\TransferOwnershipRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Requests\Tenant\UpdateUserRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function current(Request $request)
    {
        $user = $request->user();

        if (! $user->tenant_id) {
            return response()->json(['message' => 'User is not associated with a tenant'], 400);
        }

        return response()->json($user->tenant);
    }

    public function update(UpdateTenantRequest $request)
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $this->authorize('update', $tenant);

        $tenant->update($request->only(['name', 'slug', 'description', 'settings']));

        $this->auditLogger->activity('tenant.updated', $user, $tenant);

        return response()->json([
            'message' => 'Tenant updated successfully',
            'tenant' => $tenant,
        ]);
    }

    public function inviteUser(InviteUserRequest $request)
    {
        $owner = $request->user();
        $tenant = $owner->tenant;

        $this->authorize('inviteUsers', $tenant);

        $exists = User::where('email', $request->email)
            ->where('tenant_id', $owner->tenant_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'User is already in this tenant'], 409);
        }

        $newUser = User::create([
            'name' => explode('@', $request->email)[0],
            'email' => $request->email,
            'password' => Hash::make(Str::random(32)),
            'tenant_id' => $owner->tenant_id,
            'role' => $request->role,
        ]);

        $this->auditLogger->activity('user.invited', $owner, $newUser);

        return response()->json([
            'message' => 'User invited successfully',
            'user' => $newUser,
        ], 201);
    }

    public function getUsers(Request $request)
    {
        $user = $request->user();

        if (! $user->tenant_id) {
            return response()->json(['message' => 'User is not associated with a tenant'], 400);
        }

        return response()->json(
            User::where('tenant_id', $user->tenant_id)
                ->latest()
                ->paginate($this->perPage($request))
        );
    }

    public function createTenantUser(CreateTenantUserRequest $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('manageUsers', $tenant);

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenantId,
            'role' => $request->role,
        ]);

        $this->auditLogger->activity('user.created', $request->user(), $newUser);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $newUser,
        ], 201);
    }

    public function listTenantUsers(Request $request, int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->authorize('manageUsers', $tenant);

        return response()->json(
            User::where('tenant_id', $tenantId)
                ->latest()
                ->paginate($this->perPage($request))
        );
    }

    public function updateUser(UpdateUserRequest $request, int $userId)
    {
        $target = User::findOrFail($userId);
        $this->authorize('update', $target);

        $data = $request->only(['name', 'email', 'role']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $target->update($data);

        $this->auditLogger->activity('user.updated', $request->user(), $target);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $target,
        ]);
    }

    public function deleteUser(Request $request, int $userId)
    {
        $target = User::findOrFail($userId);
        $this->authorize('delete', $target);

        $target->delete();

        $this->auditLogger->activity('user.deleted', $request->user(), $target);

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function removeUser(RemoveUserRequest $request)
    {
        $owner = $request->user();
        $userToRemove = User::findOrFail($request->user_id);

        $this->authorize('removeFromTenant', $userToRemove);

        $userToRemove->delete();

        $this->auditLogger->activity('user.removed', $owner, $userToRemove);

        return response()->json(['message' => 'User removed successfully']);
    }

    public function transferOwnership(TransferOwnershipRequest $request)
    {
        $owner = $request->user();
        $tenant = $owner->tenant;

        $this->authorize('transferOwnership', $tenant);

        $newOwner = User::where('tenant_id', $tenant->id)
            ->findOrFail($request->user_id);

        $tenant->update(['owner_id' => $newOwner->id]);

        $this->auditLogger->activity('tenant.ownership_transferred', $owner, $tenant, [
            'new_owner_id' => $newOwner->id,
        ]);

        return response()->json([
            'message' => 'Ownership transferred',
            'tenant' => $tenant->fresh(),
            'owner' => $newOwner,
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $this->authorize('delete', $tenant);

        $this->auditLogger->audit('tenant.deleted', $user, $tenant->id, Tenant::class, $tenant->id);

        $tenant->delete();

        return response()->json(['message' => 'Tenant soft-deleted']);
    }
}
