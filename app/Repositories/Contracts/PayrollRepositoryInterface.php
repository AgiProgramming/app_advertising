<?php

namespace App\Repositories\Contracts;

interface PayrollRepositoryInterface
{
    public function getAll(?string $employeeId = null, ?string $period = null, ?string $paymentStatus = null);

    public function getById(string $id);

    public function getBySpkId(string $spkId);

    public function create(array $data);

    public function update(string $id, array $data);

    public function delete(string $id);

    public function generateSlipNumber(): string;

    public function getTotalCashAdvanceDeducted(string $employeeId): float;

    public function getTotalPaidSalary(?string $period = null): float;
}
