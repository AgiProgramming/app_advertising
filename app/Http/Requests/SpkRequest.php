<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class SpkRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }
    public function rules(): array {
        return [
            'date' => 'required|date',
            'shop_id' => 'required|exists:shops,id',
            'employee_id' => 'nullable|exists:employees,id',
            'job_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'spk_value' => 'required|numeric|min:0|max:999999999999',
            'job_status' => 'nullable|string|in:Belum Dikerjakan,Sedang Dikerjakan,Selesai',
            'payment_status' => 'nullable|string|in:Belum Dibayar,Sudah Dibayar',
            'notes' => 'nullable|string|max:500',
        ];
    }
}