<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Terima juga kunci "employees" sebagai alias "members".
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('members') && $this->has('employees')) {
            $this->merge(['members' => $this->input('employees')]);
        }
    }

    public function rules(): array
    {
        $spkId = $this->route('spk');

        return [
            'spk_number' => 'nullable|string|max:100|unique:spks,spk_number'.($spkId ? ','.$spkId : ''),
            'client_name' => 'nullable|string|max:150',
            'date' => 'required|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'duration_days' => 'nullable|integer|min:1|max:365',
            'shop_id' => 'required|exists:shops,id',
            'employee_id' => 'nullable|exists:employees,id',
            'job_name' => 'required|string|max:150',
            'location' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:1000',
            'spk_value' => 'required|numeric|min:0|max:999999999999',
            'job_status' => 'nullable|string|in:Belum Dikerjakan,Sedang Dikerjakan,Selesai',
            'payment_status' => 'nullable|string|in:Belum Dibayar,Sudah Dibayar',
            'notes' => 'nullable|string|max:500',

            'members' => 'nullable|array',
            'members.*.employee_id' => 'required|exists:employees,id',
            'members.*.role' => 'nullable|string|in:worker,replacement',
            'members.*.replacement_for_id' => 'nullable|exists:employees,id',
            'members.*.days_worked' => 'nullable|integer|min:0|max:365',
        ];
    }

    public function messages(): array
    {
        return [
            'members.*.employee_id.required' => 'Setiap anggota SPK harus memilih karyawan.',
            'members.*.employee_id.exists' => 'Karyawan yang dipilih tidak ditemukan.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
