<?php
namespace App\Services;

use App\Models\Shop;
use App\Models\Employee;
use App\Models\Spk;
use App\Models\CashAdvance;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getAdminDashboard(): array
    {
        $totalBorrowed = (float) CashAdvance::sum('amount');
        $totalDeducted = (float) Payroll::sum('cash_advance_deduction');
        $remainingCashAdvance = max(0.0, round($totalBorrowed - $totalDeducted, 2));

        return [
            'overview' => [
                'total_shops' => Shop::count(),
                'active_shops' => Shop::where('status', 'Aktif')->count(),
                'total_employees' => Employee::count(),
                'active_employees' => Employee::where('is_active', true)->count(),
            ],
            'spk' => [
                'total_spk' => Spk::count(),
                'pending' => Spk::where('job_status', 'Belum Dikerjakan')->count(),
                'in_progress' => Spk::where('job_status', 'Sedang Dikerjakan')->count(),
                'completed' => Spk::where('job_status', 'Selesai')->count(),
                'total_spk_value' => (float) Spk::sum('spk_value'),
            ],
            'cash_advance' => [
                'total_borrowed' => $totalBorrowed,
                'total_deducted' => $totalDeducted,
                'remaining_unpaid' => $remainingCashAdvance,
            ],
            'payroll' => [
                'total_slips' => Payroll::count(),
                'total_paid_salary' => (float) Payroll::where('payment_status', 'Sudah Dibayar')->sum('net_salary'),
                'total_unpaid_salary' => (float) Payroll::where('payment_status', 'Belum Dibayar')->sum('net_salary'),
            ],
            'recent_spks' => Spk::with(['shop', 'employee'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    public function getOwnerDashboard(?string $period = null): array
    {
        $spkQuery = Spk::query();
        $payrollQuery = Payroll::query();

        if ($period) {
            $payrollQuery->where('period', $period);
            $spkQuery->where('date', 'LIKE', "{$period}%");
        }

        $totalSpkValue = (float) $spkQuery->sum('spk_value');
        $ppnAmount = round($totalSpkValue * 0.03, 2);
        $netSpkValue = round($totalSpkValue - $ppnAmount, 2);

        $totalPayrollPaid = (float) (clone $payrollQuery)->where('payment_status', 'Sudah Dibayar')->sum('net_salary');
        $totalPayrollPending = (float) (clone $payrollQuery)->where('payment_status', 'Belum Dibayar')->sum('net_salary');
        $totalPayrollAll = (float) $payrollQuery->sum('net_salary');

        // Estimasi Margin Operasional Kasar (Omset Bersih SPK dikurangi Total Gaji Karyawan)
        $estimatedOperatingMargin = round($netSpkValue - $totalPayrollAll, 2);

        // Rekap performa order per toko
        $topShops = Shop::withCount(['spks' => function ($q) use ($period) {
                if ($period) $q->where('date', 'LIKE', "{$period}%");
            }])
            ->withSum(['spks' => function ($q) use ($period) {
                if ($period) $q->where('date', 'LIKE', "{$period}%");
            }], 'spk_value')
            ->orderBy('spks_sum_spk_value', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($shop) {
                return [
                    'id' => $shop->id,
                    'code' => $shop->code,
                    'name' => $shop->name,
                    'total_spk_count' => $shop->spks_count ?? 0,
                    'total_revenue' => (float) ($shop->spks_sum_spk_value ?? 0),
                ];
            });

        return [
            'period' => $period ?? 'Semua Periode',
            'financial' => [
                'gross_revenue' => $totalSpkValue,
                'estimated_ppn' => $ppnAmount,
                'net_revenue' => $netSpkValue,
                'total_salary_expense' => $totalPayrollAll,
                'paid_salary_expense' => $totalPayrollPaid,
                'unpaid_salary_expense' => $totalPayrollPending,
                'operating_margin' => $estimatedOperatingMargin,
            ],
            'spk_performance' => [
                'total_spks' => $spkQuery->count(),
                'completed_spks' => (clone $spkQuery)->where('job_status', 'Selesai')->count(),
                'paid_spks' => (clone $spkQuery)->where('payment_status', 'Sudah Dibayar')->count(),
            ],
            'top_shops' => $topShops,
        ];
    }

    public function getKaryawanDashboard(string $employeeId): array
    {
        $employee = Employee::findOrFail($employeeId);

        $totalBorrowed = (float) CashAdvance::where('employee_id', $employeeId)->sum('amount');
        $totalDeducted = (float) Payroll::where('employee_id', $employeeId)->sum('cash_advance_deduction');
        $remainingCashAdvance = max(0.0, round($totalBorrowed - $totalDeducted, 2));

        $totalPaidSalary = (float) Payroll::where('employee_id', $employeeId)
            ->where('payment_status', 'Sudah Dibayar')
            ->sum('net_salary');

        $totalPendingSalary = (float) Payroll::where('employee_id', $employeeId)
            ->where('payment_status', 'Belum Dibayar')
            ->sum('net_salary');

        $latestPayroll = Payroll::with('spk')
            ->where('employee_id', $employeeId)
            ->orderBy('created_at', 'desc')
            ->first();

        $activeSpks = Spk::with('shop')
            ->where('employee_id', $employeeId)
            ->where('job_status', '!=', 'Selesai')
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        return [
            'employee' => [
                'id' => $employee->id,
                'nik' => $employee->nik,
                'name' => $employee->name,
                'position' => $employee->position,
            ],
            'spk' => [
                'total_assigned' => Spk::where('employee_id', $employeeId)->count(),
                'pending' => Spk::where('employee_id', $employeeId)->where('job_status', 'Belum Dikerjakan')->count(),
                'in_progress' => Spk::where('employee_id', $employeeId)->where('job_status', 'Sedang Dikerjakan')->count(),
                'completed' => Spk::where('employee_id', $employeeId)->where('job_status', 'Selesai')->count(),
            ],
            'financial' => [
                'total_salary_received' => $totalPaidSalary,
                'pending_salary' => $totalPendingSalary,
                'remaining_cash_advance' => $remainingCashAdvance,
            ],
            'latest_payroll' => $latestPayroll,
            'active_tasks' => $activeSpks,
        ];
    }
}
