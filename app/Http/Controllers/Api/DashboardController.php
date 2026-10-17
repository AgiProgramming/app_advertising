<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function adminDashboard()
    {
        $data = $this->dashboardService->getAdminDashboard();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function ownerDashboard(Request $request)
    {
        $period = $request->query('period');
        $data = $this->dashboardService->getOwnerDashboard($period);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function karyawanDashboard(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil karyawan tidak ditemukan untuk akun ini.',
            ], 404);
        }

        $data = $this->dashboardService->getKaryawanDashboard($user->employee->id);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
