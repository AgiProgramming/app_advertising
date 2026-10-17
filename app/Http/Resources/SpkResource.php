<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpkResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'spk_number' => $this->spk_number,
            'date' => $this->date,
            'shop' => [
                'id' => $this->shop->id,
                'code' => $this->shop->code,
                'name' => $this->shop->name,
            ],
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
            ] : null,
            'job_name' => $this->job_name,
            'description' => $this->description,
            'spk_value' => $this->spk_value,
            'job_status' => $this->job_status,
            'payment_status' => $this->payment_status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}