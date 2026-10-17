<?php

namespace App\Services;

use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeService
{
    protected $employeeRepository;

    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function getAllEmployees()
    {
        return $this->employeeRepository->getAll();
    }

    public function getEmployeeById(string $id)
    {
        return $this->employeeRepository->getById($id);
    }

    public function createEmployee(array $data)
    {
        return $this->employeeRepository->create($data);
    }

    public function updateEmployee(string $id, array $data)
    {
        return $this->employeeRepository->update($id, $data);
    }

    public function deleteEmployee(string $id)
    {
        return $this->employeeRepository->delete($id);
    }
}
