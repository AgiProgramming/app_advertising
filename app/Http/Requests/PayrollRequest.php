<?php

namespace App\Http\Requests;

use App\Models\Spk;
use Illuminate\Foundation\Http\FormRequest;

class PayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kompatibilitas klien lama: kirim spk_id saja, bukan employee_id + period.
     * Karyawan dan periode diambil dari SPK tersebut.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('employee_id') && $this->has('spk_id')) {
            $spk = Spk::with('assignments')->find($this->input('spk_id'));

            if ($spk) {
                $employeeId = optional($spk->assignments->firstWhere('role', 'worker'))->employee_id
                    ?? $spk->employee_id;

                $this->merge([
                    'employee_id' => $employeeId,
                    'period' => $this->input('period') ?: optional($spk->date)->format('Y-m'),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'employee_id' => $this->isMethod('post')
                ? 'required|exists:employees,id'
                : 'sometimes|exists:employees,id',
            'period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'ppn_rate' => 'nullable|numeric|min:0|max:100',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'meal_rate' => 'nullable|numeric|min:0|max:999999999999',
            'cash_advance_deduction' => 'nullable|numeric|min:0|max:999999999999',
            'payment_status' => 'nullable|string|in:Belum Dibayar,Sudah Dibayar',
            'payment_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Pilih karyawan yang akan dibuatkan slip.',
            'period.regex' => 'Format periode harus YYYY-MM, contoh 2026-10.',
        ];
    }
}
