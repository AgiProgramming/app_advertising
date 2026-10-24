<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashAdvanceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SpkController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Verifikasi keaslian slip lewat QR (publik, hanya ringkasan slip).
Route::get('/verify/{token}', [PayrollController::class, 'verify']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Slip PDF — semua role, Karyawan dibatasi slip miliknya di controller.
    Route::get('/payrolls/{id}/pdf', [PayrollController::class, 'downloadPdf']);

    // Notifikasi slip gaji (feed in-app + pendaftaran device FCM).
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/device-token', [NotificationController::class, 'store']);
    Route::delete('/device-token', [NotificationController::class, 'destroy']);

    // Rute Khusus Admin
    Route::middleware('role:Admin')->group(function () {
        // Master Toko
        Route::apiResource('shops', ShopController::class);
        // Master Karyawan
        Route::apiResource('employees', EmployeeController::class);
        // SPK
        Route::apiResource('spks', SpkController::class);
        // Kasbon Karyawan
        Route::apiResource('cash-advances', CashAdvanceController::class);
        Route::get('/employees/{employeeId}/cash-advances-total', [CashAdvanceController::class, 'summaryByEmployee']);
        // Payroll / Slip Gaji
        Route::post('payrolls/preview', [PayrollController::class, 'preview']);
        Route::post('payrolls/generate', [PayrollController::class, 'generate']);
        Route::patch('payrolls/{id}/payment', [PayrollController::class, 'updatePayment']);
        Route::apiResource('payrolls', PayrollController::class);
        // Dashboard Admin
        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard']);
    });

    // Laporan Rekapitulasi (Bisa diakses Admin & Owner)
    Route::middleware('role:Admin,Owner')->group(function () {
        Route::get('/reports/spk', [ReportController::class, 'spkReport']);
        Route::get('/reports/payroll', [ReportController::class, 'payrollReport']);
        Route::get('/reports/cash-advances', [ReportController::class, 'cashAdvanceReport']);
    });

    // Rute Khusus Owner
    Route::middleware('role:Owner')->group(function () {
        Route::get('/owner/spks', [SpkController::class, 'index']);
        Route::get('/owner/payrolls', [PayrollController::class, 'index']);
        Route::get('/owner/payrolls/{id}', [PayrollController::class, 'show']);
        Route::get('/owner/dashboard', [DashboardController::class, 'ownerDashboard']);
        Route::get('/owner/reports/spk', [ReportController::class, 'spkReport']);
        Route::get('/owner/reports/payroll', [ReportController::class, 'payrollReport']);
        Route::get('/owner/reports/cash-advances', [ReportController::class, 'cashAdvanceReport']);
    });

    // Rute Khusus Karyawan
    Route::middleware('role:Karyawan')->group(function () {
        Route::get('/karyawan/spks', [SpkController::class, 'index']);
        Route::get('/karyawan/cash-advances', [CashAdvanceController::class, 'index']);
        Route::get('/karyawan/payrolls', [PayrollController::class, 'index']);
        Route::get('/karyawan/payrolls/{id}', [PayrollController::class, 'show']);
        Route::get('/karyawan/dashboard', [DashboardController::class, 'karyawanDashboard']);
    });
});
