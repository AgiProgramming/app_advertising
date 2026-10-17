<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $originalSpk = (float) $this->original_spk_value;
        $netSpk = (float) $this->net_spk_value;
        $salaryPercentage = (float) $this->salary_percentage;
        $grossSalary = $netSpk * ($salaryPercentage / 100);
        $ppnAmount = $originalSpk - $netSpk;

        return [
            'id' => $this->id,
            'slip_number' => $this->slip_number,
            'period' => $this->period,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'nik' => $this->employee->nik,
                'name' => $this->employee->name,
                'position' => $this->employee->position,
            ] : null,
            'spk' => $this->spk ? [
                'id' => $this->spk->id,
                'spk_number' => $this->spk->spk_number,
                'job_name' => $this->spk->job_name,
                'job_status' => $this->spk->job_status,
                'shop' => $this->spk->shop ? [
                    'id' => $this->spk->shop->id,
                    'code' => $this->spk->shop->code,
                    'name' => $this->spk->shop->name,
                ] : null,
            ] : null,
            'original_spk_value' => $originalSpk,
            'ppn_percentage' => 3.0,
            'ppn_amount' => round($ppnAmount, 2),
            'net_spk_value' => $netSpk,
            'salary_percentage' => $salaryPercentage,
            'gross_salary' => round($grossSalary, 2),
            'meal_allowance' => (float) $this->meal_allowance,
            'cash_advance_deduction' => (float) $this->cash_advance_deduction,
            'net_salary' => (float) $this->net_salary,
            'payment_status' => $this->payment_status,
            'payment_date' => $this->payment_date,
            'created_at' => $this->created_at,
        ];
    }
}
