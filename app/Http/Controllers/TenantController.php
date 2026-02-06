<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    /**
     * Get current tenant details.
     */
    public function current(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->tenant_id) {
            return response()->json(['message' => 'User is not associated with a tenant'], 400);
        }

        return response()->json($user->tenant);
    }

    /**
     * Create a new tenant (called during user registration).
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants',
        ]);

        $tenant = Tenant::create([
            'name' => $request->name,
            'slug' => Str::slug($request->slug),
            'description' => $request->description ?? null,
        ]);

        return response()->json([
            'message' => 'Tenant created successfully',
            'tenant' => $tenant,
        ], 201);
    }

    /**
     * Update tenant information.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->tenant_id) {
            return response()->json(['message' => 'User is not associated with a tenant'], 400);
        }

        // Only admin can update tenant
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tenant = $user->tenant;

        $request->validate([
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:tenants,slug,' . $tenant->id,
            'description' => 'nullable|string',
            'settings' => 'nullable|json',
        ]);

        $tenant->update($request->only(['name', 'slug', 'description', 'settings']));

        return response()->json([
            'message' => 'Tenant updated successfully',
            'tenant' => $tenant,
        ]);
    }

    /**
     * Invite user to tenant.
     */
    public function inviteUser(Request $request)
    {
        $owner = $request->user();

        if (!$owner || !$owner->tenant_id) {
            return response()->json(['message' => 'User is not associated with a tenant'], 400);
        }

        if ($owner->role !== 'admin') {
            return response()->json(['message' => 'Only admins can invite users'], 403);
        }

        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,member',
        ]);

        // Check if user already exists in this tenant
        $user = User::where('email', $request->email)
            ->where('tenant_id', $owner->tenant_id)
            ->first();

        if ($user) {
            return response()->json(['message' => 'User is already in this tenant'], 409);
        }

        // For now, we'll create a placeholder user. In production, you'd send an invitation email
        $newUser = User::create([
            'name' => explode('@', $request->email)[0],
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make(Str::random(32)),
            'tenant_id' => $owner->tenant_id,
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User invited successfully',
            'user' => $newUser,
        ], 201);
    }

    /**
     * Get all users in tenant.
     */
    public function getUsers(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->tenant_id) {
            return response()->json(['message' => 'User is not associated with a tenant'], 400);
        }

        $users = User::where('tenant_id', $user->tenant_id)->get();

        return response()->json($users);
    }

    /**
     * Create a user within a tenant.
     */
    public function createTenantUser(Request $request, int $tenantId)
    {
        $actor = $request->user();

        if (!$actor || $actor->tenant_id !== $tenantId || $actor->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,NULL,id,tenant_id,' . $tenantId,
            'role' => 'required|in:admin,member',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenantId,
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $newUser,
        ], 201);
    }

    /**
     * List users within a tenant.
     */
    public function listTenantUsers(Request $request, int $tenantId)
    {
        $actor = $request->user();

        if (!$actor || $actor->tenant_id !== $tenantId || $actor->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::where('tenant_id', $tenantId)->get();

        return response()->json($users);
    }

    /**
     * Update a user within the actor's tenant.
     */
    public function updateUser(Request $request, int $userId)
    {
        $actor = $request->user();

        if (!$actor || $actor->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $target = User::find($userId);

        if (!$target) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($target->tenant_id !== $actor->tenant_id) {
            return response()->json(['message' => 'User is not in this tenant'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $userId . ',id,tenant_id,' . $actor->tenant_id,
            'role' => 'sometimes|in:admin,member',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        $data = $request->only(['name', 'email', 'role']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $target->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $target,
        ]);
    }

    /**
     * Delete a user within the actor's tenant.
     */
    public function deleteUser(Request $request, int $userId)
    {
        $actor = $request->user();

        if (!$actor || $actor->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $target = User::find($userId);

        if (!$target) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($target->tenant_id !== $actor->tenant_id) {
            return response()->json(['message' => 'User is not in this tenant'], 403);
        }

        if ($target->id === $actor->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 400);
        }

        $target->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Remove user from tenant.
     */
    public function removeUser(Request $request)
    {
        $owner = $request->user();

        if (!$owner || !$owner->tenant_id) {
            return response()->json(['message' => 'User is not associated with a tenant'], 400);
        }

        if ($owner->role !== 'admin') {
            return response()->json(['message' => 'Only admins can remove users'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userToRemove = User::find($request->user_id);

        if ($userToRemove->tenant_id !== $owner->tenant_id) {
            return response()->json(['message' => 'User is not in this tenant'], 400);
        }

        if ($userToRemove->id === $owner->id) {
            return response()->json(['message' => 'Cannot remove yourself'], 400);
        }

        $userToRemove->delete();

        return response()->json(['message' => 'User removed successfully']);
    }
}
