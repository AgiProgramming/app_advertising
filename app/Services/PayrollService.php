<?php
namespace App\Services;

use App\Repositories\Contracts\PayrollRepositoryInterface;
use App\Repositories\Contracts\SpkRepositoryInterface;
use App\Repositories\Contracts\CashAdvanceRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    protected $payrollRepository;
    protected $spkRepository;
    protected $cashAdvanceRepository;

    public function __construct(
        PayrollRepositoryInterface $payrollRepository,
        SpkRepositoryInterface $spkRepository,
        CashAdvanceRepositoryInterface $cashAdvanceRepository
    ) {
        $this->payrollRepository = $payrollRepository;
        $this->spkRepository = $spkRepository;
        $this->cashAdvanceRepository = $cashAdvanceRepository;
    }

    public function getAllPayrolls(?string $employeeId = null, ?string $period = null, ?string $paymentStatus = null)
    {
        return $this->payrollRepository->getAll($employeeId, $period, $paymentStatus);
    }

    public function getPayrollById(string $id)
    {
        return $this->payrollRepository->getById($id);
    }

    public function getRemainingCashAdvance(string $employeeId): float
    {
        $totalBorrowed = $this->cashAdvanceRepository->getTotalByEmployee($employeeId);
        $totalDeducted = $this->payrollRepository->getTotalCashAdvanceDeducted($employeeId);
        $remaining = $totalBorrowed - $totalDeducted;
        return max(0.0, round($remaining, 2));
    }

    public function calculatePreview(array $params): array
    {
        $spk = $this->spkRepository->getById($params['spk_id']);
        if (!$spk->employee) {
            throw new Exception("SPK belum memiliki karyawan yang ditugaskan.");
        }

        $employee = $spk->employee;
        $originalSpk = (float) $spk->spk_value;
        $ppnAmount = round($originalSpk * 0.03, 2);
        $netSpk = round($originalSpk - $ppnAmount, 2);

        $salaryPercentage = isset($params['salary_percentage']) ? (float) $params['salary_percentage'] : 22.0;
        $grossSalary = round($netSpk * ($salaryPercentage / 100), 2);

        $remainingCashAdvance = $this->getRemainingCashAdvance($employee->id);

        $mealAllowance = isset($params['meal_allowance']) ? round((float) $params['meal_allowance'], 2) : 0.0;

        // Tunjangan tidak boleh melebihi gaji kotor.
        if ($mealAllowance > $grossSalary) {
            throw new Exception(
                "Tunjangan uang makan (Rp " . number_format($mealAllowance, 2, ',', '.') .
                ") melebihi gaji kotor (Rp " . number_format($grossSalary, 2, ',', '.') . ")."
            );
        }

        // Potongan kasbon default: jika diisi gunakan input, jika tidak diisi potong hingga sisa kasbon.
        // Nilai dari klien TIDAK PERNAH boleh melebihi sisa utang karyawan.
        if (isset($params['cash_advance_deduction'])) {
            $requested = round((float) $params['cash_advance_deduction'], 2);

            if ($requested > $remainingCashAdvance) {
                throw new Exception(
                    "Potongan kasbon (Rp " . number_format($requested, 2, ',', '.') .
                    ") melebihi sisa kasbon karyawan (Rp " . number_format($remainingCashAdvance, 2, ',', '.') . ")."
                );
            }

            $cashAdvanceDeduction = $requested;
        } else {
            $availableSalary = max(0.0, $grossSalary - $mealAllowance);
            $cashAdvanceDeduction = round(min($remainingCashAdvance, $availableSalary), 2);
        }

        $netSalary = max(0.0, round($grossSalary - $mealAllowance - $cashAdvanceDeduction, 2));

        return [
            'spk' => $spk,
            'employee' => $employee,
            'original_spk_value' => $originalSpk,
            'ppn_percentage' => 3.0,
            'ppn_amount' => $ppnAmount,
            'net_spk_value' => $netSpk,
            'salary_percentage' => $salaryPercentage,
            'gross_salary' => $grossSalary,
            'meal_allowance' => $mealAllowance,
            'remaining_cash_advance' => $remainingCashAdvance,
            'cash_advance_deduction' => $cashAdvanceDeduction,
            'net_salary' => $netSalary,
        ];
    }

    public function createPayroll(array $data)
    {
        $existing = $this->payrollRepository->getBySpkId($data['spk_id']);
        if ($existing) {
            throw new Exception("SPK ini sudah memiliki slip gaji ({$existing->slip_number}).");
        }

        $calc = $this->calculatePreview($data);
        $spk = $calc['spk'];

        return DB::transaction(function () use ($data, $calc, $spk) {
            $payload = [
                'employee_id' => $spk->employee_id,
                'spk_id' => $spk->id,
                'period' => $data['period'],
                'original_spk_value' => $calc['original_spk_value'],
                'net_spk_value' => $calc['net_spk_value'],
                'salary_percentage' => $calc['salary_percentage'],
                'meal_allowance' => $calc['meal_allowance'],
                'cash_advance_deduction' => $calc['cash_advance_deduction'],
                'net_salary' => $calc['net_salary'],
                'payment_status' => $data['payment_status'] ?? 'Belum Dibayar',
                'payment_date' => $data['payment_date'] ?? null,
                // Simpan status SPK sebelum diubah, agar bisa dipulihkan saat slip dihapus.
                'previous_job_status' => $spk->job_status,
            ];

            $payroll = $this->payrollRepository->create($payload);

            // Update status pekerjaan SPK menjadi Selesai jika belum selesai
            if ($spk->job_status !== 'Selesai') {
                $this->spkRepository->update($spk->id, ['job_status' => 'Selesai']);
            }

            return $payroll;
        });
    }

    public function updatePayroll(string $id, array $data)
    {
        $payroll = $this->getPayrollById($id);
        $calc = $this->calculatePreview(array_merge([
            'spk_id' => $payroll->spk_id,
            'salary_percentage' => $payroll->salary_percentage,
            'meal_allowance' => $payroll->meal_allowance,
            'cash_advance_deduction' => $payroll->cash_advance_deduction,
        ], $data));

        $payload = [
            'period' => $data['period'] ?? $payroll->period,
            'salary_percentage' => $calc['salary_percentage'],
            'meal_allowance' => $calc['meal_allowance'],
            'cash_advance_deduction' => $calc['cash_advance_deduction'],
            'net_salary' => $calc['net_salary'],
        ];

        if (isset($data['payment_status'])) {
            $payload['payment_status'] = $data['payment_status'];
        }
        if (isset($data['payment_date'])) {
            $payload['payment_date'] = $data['payment_date'];
        }

        return $this->payrollRepository->update($id, $payload);
    }

    public function updatePaymentStatus(string $id, string $status, ?string $date = null)
    {
        $payroll = $this->getPayrollById($id);
        $paymentDate = ($status === 'Sudah Dibayar') ? ($date ?? date('Y-m-d')) : null;

        return DB::transaction(function () use ($payroll, $id, $status, $paymentDate) {
            $updatedPayroll = $this->payrollRepository->update($id, [
                'payment_status' => $status,
                'payment_date' => $paymentDate,
            ]);

            // Jika status sudah dibayar, perbarui juga payment_status pada SPK
            if ($payroll->spk) {
                $this->spkRepository->update($payroll->spk_id, [
                    'payment_status' => $status,
                ]);
            }

            return $updatedPayroll;
        });
    }

    public function deletePayroll(string $id)
    {
        $payroll = $this->getPayrollById($id);

        return DB::transaction(function () use ($payroll) {
            $spk = $payroll->spk;

            $this->payrollRepository->delete($payroll->id);

            // Kembalikan status SPK seperti sebelum slip dibuat agar data tetap konsisten.
            if ($spk) {
                $this->spkRepository->update($spk->id, [
                    'job_status' => $payroll->previous_job_status ?? 'Sedang Dikerjakan',
                    'payment_status' => 'Belum Dibayar',
                ]);
            }

            return true;
        });
    }
}
