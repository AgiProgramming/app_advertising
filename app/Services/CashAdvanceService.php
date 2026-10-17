<?php

namespace App\Services;

use App\Repositories\Contracts\CashAdvanceRepositoryInterface;

class CashAdvanceService
{
    protected $cashAdvanceRepository;

    public function __construct(CashAdvanceRepositoryInterface $cashAdvanceRepository)
    {
        $this->cashAdvanceRepository = $cashAdvanceRepository;
    }

    public function getAllCashAdvances(?string $employeeId = null)
    {
        return $this->cashAdvanceRepository->getAll($employeeId);
    }

    public function getCashAdvanceById(string $id)
    {
        return $this->cashAdvanceRepository->getById($id);
    }

    public function createCashAdvance(array $data)
    {
        return $this->cashAdvanceRepository->create($data);
    }

    public function updateCashAdvance(string $id, array $data)
    {
        return $this->cashAdvanceRepository->update($id, $data);
    }

    public function deleteCashAdvance(string $id)
    {
        return $this->cashAdvanceRepository->delete($id);
    }

    public function getTotalCashAdvanceByEmployee(string $employeeId): float
    {
        return $this->cashAdvanceRepository->getTotalByEmployee($employeeId);
    }
}
