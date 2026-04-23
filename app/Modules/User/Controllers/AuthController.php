<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Requests\User\RegisterRequest;
use App\Modules\User\Requests\User\LoginRequest;
use App\Modules\User\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Modules\User\Models\User;
use App\Modules\User\Resources\LoginResource;

class AuthController extends Controller
{
    public function __construct(private UserRepositoryInterface $users) {}

    /**
     * ✅ Register (API)
     */
    public function register(RegisterRequest $request)
    {
        $user = $this->users->create($request->validated());

        // إنشاء توكن مباشر بعد التسجيل
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * ✅ Login لأي مستخدم (Token-based)
     */
    public function userLogin(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // حذف التوكنات القديمة (اختياري)
        $user->tokens()->delete();

        // إنشاء توكن جديد
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * ✅ Login للأدمن فقط (Token-based)
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])
            ->with('roles')
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $allowedRoles = ['admin'];
        // $allowedRoles = ['admin', 'partner'];

        if (!$user->hasAnyRole($allowedRoles)) {
            return response()->json([
                'message' => 'You are not authorized to access this panel'
            ], 403);
        }

        $user->tokens()->delete();

        $tokenAbilities = $user->roles
            ->pluck('name')
            ->filter(fn($role) => in_array($role, $allowedRoles))
            ->map(fn($role) => 'role:' . $role)
            ->values()
            ->toArray();

        $token = $user->createToken('panel_token', $tokenAbilities)->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new LoginResource($user),
            'token' => $token,
        ]);
    }

    /**
     * ✅ Logout (Token-based)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * ✅ Get Authenticated User
     */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }
}
