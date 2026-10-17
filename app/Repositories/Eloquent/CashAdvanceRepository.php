<?php

namespace App\Repositories\Eloquent;

use App\Models\CashAdvance;
use App\Repositories\Contracts\CashAdvanceRepositoryInterface;

class CashAdvanceRepository implements CashAdvanceRepositoryInterface
{
    public function getAll(?string $employeeId = null)
    {
        $query = CashAdvance::with('employee')->orderBy('date', 'desc')->orderBy('created_at', 'desc');
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        return $query->get();
    }

    public function getById(string $id)
    {
        return CashAdvance::with('employee')->findOrFail($id);
    }

    public function create(array $data)
    {
        return CashAdvance::create($data);
    }

    public function update(string $id, array $data)
    {
        $cashAdvance = $this->getById($id);
        $cashAdvance->update($data);

        return $cashAdvance;
    }

    public function delete(string $id)
    {
        $cashAdvance = $this->getById($id);
        $cashAdvance->delete();

        return true;
    }

    public function getTotalByEmployee(string $employeeId)
    {
        return (float) CashAdvance::where('employee_id', $employeeId)->sum('amount');
    }
}
