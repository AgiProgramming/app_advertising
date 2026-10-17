<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function getAll()
    {
        return Employee::with('user')->orderBy('created_at', 'desc')->get();
    }

    public function getById(string $id)
    {
        return Employee::with('user')->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Ambil ID Role Karyawan
            $role = Role::where('name', 'Karyawan')->first();

            // Buat User Akun
            $user = User::create([
                'role_id' => $role->id,
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
            ]);

            // Buat Profil Employee
            return Employee::create([
                'user_id' => $user->id,
                'nik' => $data['nik'],
                'name' => $data['name'],
                'position' => $data['position'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $employee = $this->getById($id);

            // Update User jika ada password / username baru
            $userData = [];
            if (isset($data['username'])) {
                $userData['username'] = $data['username'];
            }
            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            if (! empty($userData)) {
                $employee->user->update($userData);
            }

            // Update Profil
            $employee->update([
                'nik' => $data['nik'],
                'name' => $data['name'],
                'position' => $data['position'],
                'phone' => $data['phone'] ?? $employee->phone,
                'address' => $data['address'] ?? $employee->address,
                'is_active' => $data['is_active'] ?? $employee->is_active,
            ]);

            return $employee;
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $employee = $this->getById($id);
            $userId = $employee->user_id;

            $employee->delete();
            User::where('id', $userId)->delete();

            return true;
        });
    }
}
