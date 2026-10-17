<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:72',
        ]);

        $username = mb_strtolower(trim($credentials['username']));

        // Pencarian username case-insensitive, tanpa pemetaan akun khusus.
        $user = User::with('role', 'employee')
            ->whereRaw('LOWER(username) = ?', [$username])
            ->first();

        // Satu pesan generik untuk user tidak ada maupun password salah (anti-enumerasi).
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Username atau password salah.',
            ], 401);
        }

        if (! $user->role) {
            return response()->json([
                'message' => 'Akun belum memiliki role. Hubungi admin.',
            ], 403);
        }

        // Akun karyawan yang dinonaktifkan tidak boleh masuk.
        if ($user->role->name === 'Karyawan' && $user->employee && ! $user->employee->is_active) {
            return response()->json([
                'message' => 'Akun Anda sudah dinonaktifkan. Hubungi admin.',
            ], 403);
        }

        // Cabut token lama agar token aktif tidak menumpuk di personal_access_tokens.
        $user->tokens()->where('name', 'auth_token')->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'role' => $user->role->name,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                ],
                'employee' => $user->employee,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load('role', 'employee');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                ],
                'employee' => $user->employee,
            ],
        ]);
    }
}
