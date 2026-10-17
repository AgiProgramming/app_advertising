<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollPaymentRequest;
use App\Http\Requests\PayrollRequest;
use App\Http\Resources\PayrollResource;
use App\Models\Payroll;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode as QrCodeImage;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $employeeId = $request->query('employee_id');

        // Jika user login sebagai Karyawan, batasi hanya data slip miliknya
        if ($user && $user->role && $user->role->name === 'Karyawan') {
            $employeeId = $user->employee ? $user->employee->id : null;
        }

        $period = $request->query('period');
        $paymentStatus = $request->query('payment_status');

        $payrolls = $this->payrollService->getAllPayrolls($employeeId, $period, $paymentStatus);

        return PayrollResource::collection($payrolls);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'spk_id' => 'required|exists:spks,id',
            'salary_percentage' => 'nullable|numeric|min:0|max:100',
            'meal_allowance' => 'nullable|numeric|min:0',
            'cash_advance_deduction' => 'nullable|numeric|min:0',
        ]);

        try {
            $preview = $this->payrollService->calculatePreview($request->all());

            return response()->json([
                'success' => true,
                'data' => [
                    'spk_id' => $preview['spk']->id,
                    'spk_number' => $preview['spk']->spk_number,
                    'job_name' => $preview['spk']->job_name,
                    'shop_name' => $preview['spk']->shop ? $preview['spk']->shop->name : null,
                    'employee' => [
                        'id' => $preview['employee']->id,
                        'name' => $preview['employee']->name,
                        'nik' => $preview['employee']->nik,
                    ],
                    'original_spk_value' => $preview['original_spk_value'],
                    'ppn_percentage' => $preview['ppn_percentage'],
                    'ppn_amount' => $preview['ppn_amount'],
                    'net_spk_value' => $preview['net_spk_value'],
                    'salary_percentage' => $preview['salary_percentage'],
                    'gross_salary' => $preview['gross_salary'],
                    'meal_allowance' => $preview['meal_allowance'],
                    'remaining_cash_advance' => $preview['remaining_cash_advance'],
                    'cash_advance_deduction' => $preview['cash_advance_deduction'],
                    'net_salary' => $preview['net_salary'],
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(PayrollRequest $request)
    {
        try {
            $payroll = $this->payrollService->createPayroll($request->validated());

            return new PayrollResource($payroll);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Request $request, string $id)
    {
        $payroll = $this->payrollService->getPayrollById($id);
        $user = $request->user();

        // Validasi akses jika Karyawan mencoba membuka slip karyawan lain
        if ($user && $user->role && $user->role->name === 'Karyawan') {
            if (! $user->employee || $user->employee->id !== $payroll->employee_id) {
                return response()->json(['message' => 'Forbidden. Anda tidak dapat melihat slip gaji karyawan lain.'], 403);
            }
        }

        return new PayrollResource($payroll);
    }

    public function update(PayrollRequest $request, string $id)
    {
        try {
            $payroll = $this->payrollService->updatePayroll($id, $request->validated());

            return new PayrollResource($payroll);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(string $id)
    {
        $this->payrollService->deletePayroll($id);

        return response()->json(['message' => 'Slip gaji berhasil dihapus.']);
    }

    public function updatePayment(PayrollPaymentRequest $request, string $id)
    {
        $validated = $request->validated();
        $payroll = $this->payrollService->updatePaymentStatus(
            $id,
            $validated['payment_status'],
            $validated['payment_date'] ?? null
        );

        return new PayrollResource($payroll);
    }

    /**
     * Unduh slip gaji sebagai PDF. Karyawan hanya boleh mengunduh slip miliknya.
     */
    public function downloadPdf(Request $request, string $id)
    {
        $payroll = $this->payrollService->getPayrollById($id);
        $user = $request->user();

        if ($user->role && $user->role->name === 'Karyawan') {
            if (! $user->employee || $user->employee->id !== $payroll->employee_id) {
                return response()->json([
                    'message' => 'Forbidden. Anda tidak dapat mengunduh slip karyawan lain.',
                ], 403);
            }
        }

        $verifyUrl = url('/api/verify/'.$payroll->verification_token);

        // QR dibuat sebagai PNG (dompdf tidak bisa merender SVG).
        $qrImage = (new PngWriter)->write(
            (new QrCodeImage($verifyUrl))->setSize(220)->setMargin(0)
        );
        $qrBase64 = base64_encode($qrImage->getString());

        $pdf = Pdf::loadView('pdf.slip', [
            'payroll' => $payroll,
            'verifyUrl' => $verifyUrl,
            'qrBase64' => $qrBase64,
        ])->setPaper('a5');

        return $pdf->download('slip-'.$payroll->slip_number.'.pdf');
    }

    /**
     * Verifikasi keaslian slip lewat token QR (tanpa login).
     */
    public function verify(string $token)
    {
        $payroll = Payroll::with(['employee', 'spk.shop'])
            ->where('verification_token', $token)
            ->first();

        if (! $payroll) {
            return response()->json([
                'valid' => false,
                'message' => 'Slip tidak ditemukan atau token verifikasi tidak valid.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'data' => [
                'slip_number' => $payroll->slip_number,
                'period' => $payroll->period,
                'employee_name' => $payroll->employee?->name,
                'employee_nik' => $payroll->employee?->nik
                    ? substr($payroll->employee->nik, 0, 4).str_repeat('*', max(0, strlen($payroll->employee->nik) - 4))
                    : null,
                'shop_name' => $payroll->spk?->shop?->name,
                'job_name' => $payroll->spk?->job_name,
                'net_salary' => (float) $payroll->net_salary,
                'payment_status' => $payroll->payment_status,
                'payment_date' => $payroll->payment_date,
                'issued_at' => $payroll->created_at?->toIso8601String(),
            ],
        ]);
    }
}
