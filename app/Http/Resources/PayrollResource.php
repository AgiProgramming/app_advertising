<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $grossShare = (float) $this->gross_share;
        $ppnAmount = (float) $this->ppn_amount;
        $ppnRate = (float) $this->ppn_rate;
        $commissionRate = (float) $this->commission_rate;

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

            // Rincian bagian dari setiap SPK pada periode ini.
            'items' => ($this->items ?? collect())->map(fn ($item) => [
                'id' => $item->id,
                'spk_id' => $item->spk_id,
                'spk_number' => $item->spk_number,
                'job_name' => $item->spk?->job_name,
                'spk_value' => (float) $item->spk_value,
                'slot_count' => (int) $item->slot_count,
                'duration_days' => (int) $item->duration_days,
                'days_worked' => (int) $item->days_worked,
                'daily_rate' => (float) $item->daily_rate,
                'share_amount' => (float) $item->share_amount,
            ])->values(),

            'gross_share' => $grossShare,
            'ppn_rate' => $ppnRate,
            'ppn_amount' => $ppnAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => (float) $this->commission_amount,
            'meal_rate' => (float) $this->meal_rate,
            'meal_count' => (int) $this->meal_count,
            'meal_deduction' => (float) $this->meal_deduction,
            'cash_advance_deduction' => (float) $this->cash_advance_deduction,
            'net_salary' => (float) $this->net_salary,
            'is_negative' => (float) $this->net_salary < 0,

            // Alias lama agar klien versi sebelumnya tetap terbaca.
            'original_spk_value' => $grossShare,
            'ppn_percentage' => $ppnRate,
            'net_spk_value' => round($grossShare - $ppnAmount, 2),
            'salary_percentage' => $commissionRate,
            'gross_salary' => $grossShare,
            'meal_allowance' => (float) $this->meal_deduction,

            'payment_status' => $this->payment_status,
            'payment_date' => $this->payment_date,
            'verification_token' => $this->verification_token,
            'verify_url' => $this->verification_token ? url('/api/verify/'.$this->verification_token) : null,
            'created_at' => $this->created_at,
        ];
    }
}
