<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeParam = $this->route('employee');
        $employee = $employeeParam instanceof Employee
            ? $employeeParam
            : ($employeeParam ? Employee::find($employeeParam) : null);

        $isUpdate = $employee !== null;

        return [
            'nik' => ['required', 'string', 'max:30', Rule::unique('employees', 'nik')->ignore($employee?->id)],
            'name' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            // Saat update, username/password tidak wajib diisi ulang.
            'username' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($employee?->user_id)]
                : ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'password' => $isUpdate
                ? ['nullable', 'string', 'min:6', 'max:72']
                : ['required', 'string', 'min:6', 'max:72'],
        ];
    }
}
