<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware {
    public function handle(Request $request, Closure $next, ...$roles): Response {
        $user = $request->user();

        if (!$user || !$user->role) {
            return response()->json(['message' => 'Unauthorized. Please login.'], 401);
        }

        if (!in_array($user->role->name, $roles)) {
            return response()->json(['message' => 'Forbidden. Akses ditolak untuk role Anda.'], 403);
        }

        // Karyawan yang dinonaktifkan tidak boleh memakai token yang masih tersimpan.
        if ($user->role->name === 'Karyawan' && $user->employee && !$user->employee->is_active) {
            return response()->json(['message' => 'Akun Anda sudah dinonaktifkan. Hubungi admin.'], 403);
        }

        return $next($request);
    }
}