<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function index()
    {
        $employees = $this->employeeService->getAllEmployees();

        return EmployeeResource::collection($employees);
    }

    public function store(EmployeeRequest $request)
    {
        $employee = $this->employeeService->createEmployee($request->validated());

        return new EmployeeResource($employee);
    }

    public function show(string $id)
    {
        $employee = $this->employeeService->getEmployeeById($id);

        return new EmployeeResource($employee);
    }

    public function update(EmployeeRequest $request, string $id)
    {
        $employee = $this->employeeService->updateEmployee($id, $request->validated());

        return new EmployeeResource($employee);
    }

    public function destroy(string $id)
    {
        $this->employeeService->deleteEmployee($id);

        return response()->json(['message' => 'Karyawan dan akun berhasil dihapus.']);
    }
}
