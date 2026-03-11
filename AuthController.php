<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // ─────────────────────────────────────────────
    // POST /api/auth/register
    // ─────────────────────────────────────────────
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:100',
            'nim'       => 'nullable|string|max:20',
            'prodi'     => 'nullable|string|max:100',
            'username'  => 'required|string|min:4|max:50|unique:users,username',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'nim'      => $request->nim,
            'prodi'    => $request->prodi,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'user',
        ]);

        $token = Auth::guard('api')->login($user);

        return response()->json([
            'status'  => true,
            'message' => 'Registrasi berhasil',
            'user'    => $user,
            'token'   => $this->tokenResponse($token),
        ], 201);
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/login
    // ─────────────────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (!$token = Auth::guard('api')->attempt($request->only('email', 'password'))) {
            return response()->json([
                'status'  => false,
                'message' => 'Email atau password salah',
            ], 401);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Login berhasil',
            'user'    => Auth::guard('api')->user(),
            'token'   => $this->tokenResponse($token),
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/refresh
    // ─────────────────────────────────────────────
    public function refresh(): JsonResponse
    {
        try {
            $token = Auth::guard('api')->refresh();
            return response()->json([
                'status'  => true,
                'message' => 'Token berhasil diperbarui',
                'token'   => $this->tokenResponse($token),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Token tidak valid atau sudah expired',
            ], 401);
        }
    }

    // ─────────────────────────────────────────────
    // POST /api/auth/logout
    // ─────────────────────────────────────────────
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json([
            'status'  => true,
            'message' => 'Logout berhasil',
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /api/auth/me
    // ─────────────────────────────────────────────
    public function me(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'user'   => Auth::guard('api')->user(),
        ]);
    }

    // ─────────────────────────────────────────────
    // Helper: format token response
    // ─────────────────────────────────────────────
    private function tokenResponse(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
        ];
    }
}
