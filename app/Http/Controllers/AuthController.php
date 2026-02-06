<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Register - Create new tenant and owner account
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'tenant_name' => 'required|string|max:255',
            'tenant_slug' => 'required|string|max:255|unique:tenants,slug',
        ]);

        // Check if email already exists globally
        if (User::where('email', $request->email)->exists()) {
            return response()->json(['message' => 'Email already registered'], 409);
        }

        // Create tenant
        $tenant = Tenant::create([
            'name' => $request->tenant_name,
            'slug' => Str::slug($request->tenant_slug),
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Create user as admin of the tenant
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        // Update tenant owner
        $tenant->update(['owner_id' => $user->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Account and tenant created successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'tenant' => $tenant,
        ], 201);
    }

    // Login - Handle multi-tenant login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'tenant_slug' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user has a tenant
        if (!$user->tenant_id) {
            return response()->json(['message' => 'User is not associated with any tenant'], 400);
        }

        // Check if tenant is active
        if (!$user->tenant->isActive()) {
            return response()->json(['message' => 'Tenant is inactive or suspended'], 403);
        }

        // If tenant_slug is provided, verify it matches user's tenant
        if ($request->tenant_slug && $user->tenant->slug !== $request->tenant_slug) {
            throw ValidationException::withMessages([
                'tenant_slug' => ['User does not belong to this tenant.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'tenant' => $user->tenant,
        ]);
    }

    // Forgot Password
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent.']);
        }

        return response()->json(['message' => 'Unable to send reset link.'], 400);
    }

    // Reset Password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password has been reset.']);
        }

        return response()->json(['message' => 'Unable to reset password.'], 400);
    }

    // Logout - Revoke current token
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    // Refresh - Revoke current token and issue a new one
    public function refresh(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Token refreshed successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // SSO - Stub endpoint
    public function sso(Request $request)
    {
        return response()->json([
            'message' => 'SSO is not implemented yet.',
        ], 501);
    }
}
