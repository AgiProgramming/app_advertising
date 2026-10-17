<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashAdvanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'nik' => $this->employee->nik,
                'name' => $this->employee->name,
                'position' => $this->employee->position,
            ] : null,
            'amount' => $this->amount,
            'date' => $this->date,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
