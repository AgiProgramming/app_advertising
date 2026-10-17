<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function spkReport(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date', 'shop_id', 'employee_id', 'job_status', 'payment_status']);
        $report = $this->reportService->getSpkReport($filters);

        return response()->json([
            'status' => 'success',
            'report' => $report,
        ]);
    }

    public function payrollReport(Request $request)
    {
        $filters = $request->only(['period', 'employee_id', 'payment_status']);
        $report = $this->reportService->getPayrollReport($filters);

        return response()->json([
            'status' => 'success',
            'report' => $report,
        ]);
    }

    public function cashAdvanceReport(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date', 'employee_id']);
        $report = $this->reportService->getCashAdvanceReport($filters);

        return response()->json([
            'status' => 'success',
            'report' => $report,
        ]);
    }
}
