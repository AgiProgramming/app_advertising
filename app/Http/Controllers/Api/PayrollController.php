<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollPaymentRequest;
use App\Http\Requests\PayrollRequest;
use App\Http\Resources\PayrollResource;
use App\Models\Payroll;
use App\Models\Spk;
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

    /**
     * Pratinjau gaji seorang karyawan pada satu periode (belum disimpan).
     */
    public function preview(Request $request)
    {
        // Kompatibilitas aplikasi versi lama: kirim spk_id saja.
        // Karyawan & periode diambil dari SPK tersebut.
        if (! $request->filled('employee_id') && $request->filled('spk_id')) {
            $spk = Spk::with('assignments')->find($request->input('spk_id'));

            if ($spk) {
                $request->merge([
                    'employee_id' => optional($spk->assignments->firstWhere('role', 'worker'))->employee_id
                        ?? $spk->employee_id,
                    'period' => $request->input('period') ?: optional($spk->date)->format('Y-m'),
                ]);
            }
        }

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'ppn_rate' => 'nullable|numeric|min:0|max:100',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'meal_rate' => 'nullable|numeric|min:0',
            'cash_advance_deduction' => 'nullable|numeric|min:0',
        ]);

        try {
            $calc = $this->payrollService->calculateForEmployee(
                $data['employee_id'],
                $data['period'],
                $data
            );

            return response()->json([
                'success' => true,
                'data' => $this->previewPayload($calc),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Susun rincian pratinjau agar mudah dipakai aplikasi.
     */
    protected function previewPayload(array $calc): array
    {
        return [
            'employee' => [
                'id' => $calc['employee']->id,
                'name' => $calc['employee']->name,
                'nik' => $calc['employee']->nik,
            ],
            'period' => $calc['period'],
            'items' => $calc['items'],
            'spk_count' => count($calc['items']),
            'gross_share' => $calc['gross_share'],
            'ppn_rate' => $calc['ppn_rate'],
            'ppn_amount' => $calc['ppn_amount'],
            'commission_rate' => $calc['commission_rate'],
            'commission_amount' => $calc['commission_amount'],
            'meal_rate' => $calc['meal_rate'],
            'meal_count' => $calc['meal_count'],
            'meal_deduction' => $calc['meal_deduction'],
            'remaining_cash_advance' => $calc['remaining_cash_advance'],
            'cash_advance_deduction' => $calc['cash_advance_deduction'],
            'net_salary' => $calc['net_salary'],
            'is_negative' => $calc['net_salary'] < 0,

            // Alias lama supaya aplikasi versi sebelumnya tetap menampilkan angka.
            'original_spk_value' => $calc['gross_share'],
            'net_spk_value' => round($calc['gross_share'] - $calc['ppn_amount'], 2),
            'salary_percentage' => $calc['commission_rate'],
            'gross_salary' => $calc['gross_share'],
            'meal_allowance' => $calc['meal_deduction'],
        ];
    }

    public function store(PayrollRequest $request)
    {
        try {
            $payroll = $this->payrollService->createPayroll($request->validated());

            return (new PayrollResource($payroll))->response()->setStatusCode(201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Buat slip sekaligus untuk semua karyawan yang punya SPK pada periode itu.
     */
    public function generate(Request $request)
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        try {
            $result = $this->payrollService->generateForPeriod($data['period']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $created = PayrollResource::collection(collect($result['created']))->resolve();

        return response()->json([
            'message' => count($created).' slip dibuat, '.count($result['skipped']).' dilewati.',
            'data' => [
                'period' => $result['period'],
                'created_count' => count($created),
                'skipped_count' => count($result['skipped']),
                'created' => $created,
                'skipped' => $result['skipped'],
            ],
        ], 201);
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
            'items' => $payroll->items,
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
        $payroll = Payroll::with(['employee', 'items'])
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
                'spk_count' => $payroll->items->count(),
                'gross_share' => (float) $payroll->gross_share,
                'net_salary' => (float) $payroll->net_salary,
                'payment_status' => $payroll->payment_status,
                'payment_date' => $payroll->payment_date,
                'issued_at' => $payroll->created_at?->toIso8601String(),
            ],
        ]);
    }
}
