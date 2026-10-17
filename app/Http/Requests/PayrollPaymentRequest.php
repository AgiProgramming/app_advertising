<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_status' => 'required|string|in:Belum Dibayar,Sudah Dibayar',
            'payment_date' => 'nullable|date',
        ];
    }
}
