<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'spk_id' => 'required|exists:spks,id',
            'period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'salary_percentage' => 'nullable|numeric|min:0|max:100',
            'meal_allowance' => 'nullable|numeric|min:0|max:999999999999',
            'cash_advance_deduction' => 'nullable|numeric|min:0|max:999999999999',
            'payment_status' => 'nullable|string|in:Belum Dibayar,Sudah Dibayar',
            'payment_date' => 'nullable|date',
        ];
    }
}
