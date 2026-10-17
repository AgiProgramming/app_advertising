<?php
namespace App\Repositories\Contracts;

interface CashAdvanceRepositoryInterface
{
    public function getAll(?string $employeeId = null);
    public function getById(string $id);
    public function create(array $data);
    public function update(string $id, array $data);
    public function delete(string $id);
    public function getTotalByEmployee(string $employeeId);
}
