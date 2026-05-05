<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Requests\User\RegisterRequest;
use App\Modules\User\Requests\User\LoginRequest;
use App\Modules\User\Requests\User\ChangePasswordRequest;
use App\Modules\User\Requests\User\ForgotPasswordRequest;
use App\Modules\User\Requests\User\ResetPasswordWithCodeRequest;
use App\Modules\User\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => [
                    'current_password' => ['Current password is incorrect.'],
                ],
            ], 422);
        }

        $user->password = $validated['new_password'];
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $validated = $request->validated();
        $email = strtolower((string) $validated['email']);

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'If this email exists, a verification code has been sent.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        // TODO: integrate Mail/SMS sender here.
        return response()->json([
            'message' => 'Verification code generated successfully.',
            'dev_code' => app()->environment('production') ? null : $code,
        ]);
    }

    public function resetPasswordWithCode(ResetPasswordWithCodeRequest $request)
    {
        $validated = $request->validated();
        $email = strtolower((string) $validated['email']);
        $code = (string) $validated['code'];

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$record) {
            return response()->json([
                'message' => 'Invalid verification code.',
            ], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'message' => 'Verification code expired.',
            ], 422);
        }

        if (!Hash::check($code, $record->token)) {
            return response()->json([
                'message' => 'Invalid verification code.',
                'errors' => [
                    'code' => ['Invalid verification code.'],
                ],
            ], 422);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $user->password = $validated['new_password'];
        $user->setRememberToken(Str::random(60));
        $user->save();
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }
}
