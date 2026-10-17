<?php
namespace App\Services;

use App\Models\Spk;
use App\Models\Payroll;
use App\Models\CashAdvance;

class ReportService
{
    public function getSpkReport(array $filters): array
    {
        $query = Spk::with(['shop', 'employee']);

        if (!empty($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }
        if (!empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['job_status'])) {
            $query->where('job_status', $filters['job_status']);
        }
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $items = $query->orderBy('date', 'desc')->get();

        $totalSpkValue = (float) $items->sum('spk_value');
        $ppnAmount = round($totalSpkValue * 0.03, 2);
        $netSpkValue = round($totalSpkValue - $ppnAmount, 2);

        return [
            'filters' => $filters,
            'summary' => [
                'total_spk' => $items->count(),
                'total_gross_spk_value' => $totalSpkValue,
                'total_ppn_value' => $ppnAmount,
                'total_net_spk_value' => $netSpkValue,
            ],
            'data' => $items,
        ];
    }

    public function getPayrollReport(array $filters): array
    {
        $query = Payroll::with(['employee', 'spk.shop']);

        if (!empty($filters['period'])) {
            $query->where('period', $filters['period']);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $items = $query->orderBy('period', 'desc')->orderBy('created_at', 'desc')->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_slips' => $items->count(),
                'total_original_spk' => (float) $items->sum('original_spk_value'),
                'total_net_spk' => (float) $items->sum('net_spk_value'),
                'total_meal_allowance' => (float) $items->sum('meal_allowance'),
                'total_cash_advance_deducted' => (float) $items->sum('cash_advance_deduction'),
                'total_net_salary' => (float) $items->sum('net_salary'),
            ],
            'data' => $items,
        ];
    }

    public function getCashAdvanceReport(array $filters): array
    {
        $query = CashAdvance::with('employee');

        if (!empty($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        $items = $query->orderBy('date', 'desc')->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_records' => $items->count(),
                'total_amount' => (float) $items->sum('amount'),
            ],
            'data' => $items,
        ];
    }
}
