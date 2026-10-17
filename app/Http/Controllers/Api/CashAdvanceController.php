<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashAdvanceRequest;
use App\Http\Resources\CashAdvanceResource;
use App\Services\CashAdvanceService;
use Illuminate\Http\Request;

class CashAdvanceController extends Controller
{
    protected $cashAdvanceService;

    public function __construct(CashAdvanceService $cashAdvanceService)
    {
        $this->cashAdvanceService = $cashAdvanceService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $employeeId = $request->query('employee_id');

        // Jika login sebagai Karyawan, batasi hanya data kasbon milik sendiri
        if ($user && $user->role && $user->role->name === 'Karyawan') {
            $employeeId = $user->employee ? $user->employee->id : null;
        }

        $cashAdvances = $this->cashAdvanceService->getAllCashAdvances($employeeId);

        return CashAdvanceResource::collection($cashAdvances);
    }

    public function store(CashAdvanceRequest $request)
    {
        $cashAdvance = $this->cashAdvanceService->createCashAdvance($request->validated());

        return new CashAdvanceResource($cashAdvance);
    }

    public function show(string $id)
    {
        $cashAdvance = $this->cashAdvanceService->getCashAdvanceById($id);

        return new CashAdvanceResource($cashAdvance);
    }

    public function update(CashAdvanceRequest $request, string $id)
    {
        $cashAdvance = $this->cashAdvanceService->updateCashAdvance($id, $request->validated());

        return new CashAdvanceResource($cashAdvance);
    }

    public function destroy(string $id)
    {
        $this->cashAdvanceService->deleteCashAdvance($id);

        return response()->json(['message' => 'Data kasbon berhasil dihapus.']);
    }

    public function summaryByEmployee(string $employeeId)
    {
        $total = $this->cashAdvanceService->getTotalCashAdvanceByEmployee($employeeId);

        return response()->json([
            'employee_id' => $employeeId,
            'total_cash_advance' => $total,
        ]);
    }
}
