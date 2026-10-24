<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignments = $this->assignments ?? collect();
        $slots = max(1, $assignments->where('role', 'worker')->count());
        $duration = max(1, (int) $this->duration_days);
        $value = (float) $this->spk_value;

        return [
            'id' => $this->id,
            'spk_number' => $this->spk_number,
            'client_name' => $this->client_name,
            'date' => $this->date,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'duration_days' => $duration,
            'shop' => $this->shop ? [
                'id' => $this->shop->id,
                'code' => $this->shop->code,
                'name' => $this->shop->name,
            ] : null,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
            ] : null,
            'job_name' => $this->job_name,
            'location' => $this->location,
            'description' => $this->description,
            'spk_value' => $value,
            'slot_count' => $slots,
            'daily_rate' => round($value / ($slots * $duration), 2),
            'members' => $assignments->map(fn ($a) => [
                'id' => $a->id,
                'employee_id' => $a->employee_id,
                'name' => $a->employee?->name,
                'nik' => $a->employee?->nik,
                'role' => $a->role,
                'replacement_for_id' => $a->replacement_for_id,
                'replacement_for_name' => $a->replacedEmployee?->name,
                'days_worked' => (int) $a->days_worked,
                'share_amount' => round($value * (int) $a->days_worked / ($slots * $duration), 2),
            ])->values(),
            'job_status' => $this->job_status,
            'payment_status' => $this->payment_status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
