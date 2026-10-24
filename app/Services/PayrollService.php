<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SpkEmployee;
use App\Repositories\Contracts\CashAdvanceRepositoryInterface;
use App\Repositories\Contracts\PayrollRepositoryInterface;
use App\Repositories\Contracts\SpkRepositoryInterface;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollService
{
    protected $payrollRepository;

    protected $spkRepository;

    protected $cashAdvanceRepository;

    protected NotificationService $notificationService;

    public function __construct(
        PayrollRepositoryInterface $payrollRepository,
        SpkRepositoryInterface $spkRepository,
        CashAdvanceRepositoryInterface $cashAdvanceRepository,
        NotificationService $notificationService
    ) {
        $this->payrollRepository = $payrollRepository;
        $this->spkRepository = $spkRepository;
        $this->cashAdvanceRepository = $cashAdvanceRepository;
        $this->notificationService = $notificationService;
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

        return max(0.0, round($totalBorrowed - $totalDeducted, 2));
    }

    /**
     * Hitung gaji satu karyawan untuk satu periode.
     *
     * Alur:
     *  1. Setiap SPK dinilai per hari: nilai SPK / (jumlah orang x jumlah hari).
     *  2. Bagian karyawan = nilai harian x hari dia masuk (termasuk bila sebagai pengganti).
     *  3. Bagian dari semua SPK dijumlahkan menjadi bagian kotor.
     *  4. Potong PPN, potong komisi Mann Plus, potong uang makan per SPK, potong kasbon.
     *  5. Hasil boleh minus.
     */
    public function calculateForEmployee(string $employeeId, string $period, array $options = []): array
    {
        $employee = Employee::find($employeeId);
        if (! $employee) {
            throw new Exception('Karyawan tidak ditemukan.');
        }

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            throw new Exception('Format periode harus YYYY-MM, contoh 2026-10.');
        }

        [$year, $month] = array_map('intval', explode('-', $period));

        $assignments = SpkEmployee::query()
            ->with('spk')
            ->where('employee_id', $employeeId)
            ->where('days_worked', '>', 0)
            ->whereHas('spk', function ($query) use ($year, $month) {
                $query->whereYear('date', $year)->whereMonth('date', $month);
            })
            ->get()
            ->filter(fn (SpkEmployee $a) => $a->spk !== null)
            ->sortBy(fn (SpkEmployee $a) => optional($a->spk->date)->timestamp ?? 0)
            ->values();

        $items = [];
        $grossShare = 0.0;

        foreach ($assignments as $assignment) {
            $spk = $assignment->spk;

            $slots = max(1, $spk->slotCount());
            $duration = max(1, (int) $spk->duration_days);
            $worked = min((int) $assignment->days_worked, $duration);
            $value = (float) $spk->spk_value;

            // Dihitung dari pecahan asli supaya pembulatan tidak menumpuk.
            $share = round($value * $worked / ($slots * $duration), 2);

            $items[] = [
                'spk_id' => $spk->id,
                'spk_number' => $spk->spk_number,
                'job_name' => $spk->job_name,
                'date' => optional($spk->date)->toDateString(),
                'role' => $assignment->role,
                'spk_value' => $value,
                'slot_count' => $slots,
                'duration_days' => $duration,
                'days_worked' => $worked,
                'daily_rate' => round($value / ($slots * $duration), 2),
                'share_amount' => $share,
            ];

            $grossShare += $share;
        }

        $grossShare = round($grossShare, 2);

        $ppnRate = (float) ($options['ppn_rate'] ?? config('payroll.ppn_rate', 4));
        $commissionRate = (float) ($options['commission_rate'] ?? config('payroll.commission_rate', 22));
        $mealRate = (float) ($options['meal_rate'] ?? config('payroll.meal_rate', 100000));

        $ppnAmount = round($grossShare * $ppnRate / 100, 2);
        $afterPpn = round($grossShare - $ppnAmount, 2);

        // Komisi milik Mann Plus, jadi dipotong dari bagian karyawan.
        $commissionAmount = round($afterPpn * $commissionRate / 100, 2);
        $afterCommission = round($afterPpn - $commissionAmount, 2);

        // Uang makan dipotong sekali untuk setiap SPK yang diikuti.
        $mealCount = count($items);
        $mealDeduction = round($mealRate * $mealCount, 2);

        $remainingCashAdvance = $this->getRemainingCashAdvance($employeeId);
        $cashAdvanceDeduction = $this->resolveCashAdvanceDeduction(
            $options['cash_advance_deduction'] ?? null,
            $remainingCashAdvance,
            max(0.0, round($afterCommission - $mealDeduction, 2))
        );

        // Tidak dibatasi nol: hasil minus tetap ditampilkan apa adanya.
        $netSalary = round($afterCommission - $mealDeduction - $cashAdvanceDeduction, 2);

        return [
            'employee' => $employee,
            'period' => $period,
            'items' => $items,
            'gross_share' => $grossShare,
            'ppn_rate' => $ppnRate,
            'ppn_amount' => $ppnAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'meal_rate' => $mealRate,
            'meal_count' => $mealCount,
            'meal_deduction' => $mealDeduction,
            'remaining_cash_advance' => $remainingCashAdvance,
            'cash_advance_deduction' => $cashAdvanceDeduction,
            'net_salary' => $netSalary,
        ];
    }

    /**
     * Potongan kasbon tidak boleh melebihi sisa utang.
     * Bila tidak diisi, potong sebanyak mungkin tanpa membuat gaji minus.
     */
    protected function resolveCashAdvanceDeduction($requested, float $remaining, float $available): float
    {
        if ($requested !== null && $requested !== '') {
            $requested = round((float) $requested, 2);

            if ($requested > $remaining) {
                throw new Exception(
                    'Potongan kasbon (Rp '.number_format($requested, 2, ',', '.').
                    ') melebihi sisa kasbon karyawan (Rp '.number_format($remaining, 2, ',', '.').').'
                );
            }

            return $requested;
        }

        return round(min($remaining, $available), 2);
    }

    public function createPayroll(array $data)
    {
        $period = $data['period'];
        $employeeId = $data['employee_id'];

        $exists = Payroll::where('employee_id', $employeeId)->where('period', $period)->exists();
        if ($exists) {
            throw new Exception("Karyawan ini sudah memiliki slip gaji untuk periode {$period}.");
        }

        $calc = $this->calculateForEmployee($employeeId, $period, $data);

        if (count($calc['items']) === 0) {
            throw new Exception("Karyawan ini tidak punya SPK pada periode {$period}.");
        }

        $payroll = DB::transaction(function () use ($calc, $data) {
            $payroll = $this->payrollRepository->create([
                'employee_id' => $calc['employee']->id,
                'period' => $calc['period'],
                'gross_share' => $calc['gross_share'],
                'ppn_rate' => $calc['ppn_rate'],
                'ppn_amount' => $calc['ppn_amount'],
                'commission_rate' => $calc['commission_rate'],
                'commission_amount' => $calc['commission_amount'],
                'meal_rate' => $calc['meal_rate'],
                'meal_count' => $calc['meal_count'],
                'meal_deduction' => $calc['meal_deduction'],
                'cash_advance_deduction' => $calc['cash_advance_deduction'],
                'net_salary' => $calc['net_salary'],
                'payment_status' => $data['payment_status'] ?? 'Belum Dibayar',
                'payment_date' => $data['payment_date'] ?? null,
            ]);

            $this->storeItems($payroll, $calc['items']);

            return $payroll;
        });

        // Notifikasi dikirim setelah transaksi commit agar kegagalan push
        // tidak membatalkan slip yang sudah tersimpan.
        $this->notifyPayrollIssued($payroll->fresh());

        return $payroll->fresh();
    }

    /**
     * Buat slip untuk semua karyawan yang punya SPK pada periode tersebut.
     * Karyawan yang sudah punya slip dilewati.
     */
    public function generateForPeriod(string $period): array
    {
        [$year, $month] = array_map('intval', explode('-', $period));

        $employeeIds = SpkEmployee::query()
            ->where('days_worked', '>', 0)
            ->whereHas('spk', function ($query) use ($year, $month) {
                $query->whereYear('date', $year)->whereMonth('date', $month);
            })
            ->pluck('employee_id')
            ->unique()
            ->values();

        $created = [];
        $skipped = [];

        foreach ($employeeIds as $employeeId) {
            try {
                $created[] = $this->createPayroll(['employee_id' => $employeeId, 'period' => $period]);
            } catch (\Throwable $e) {
                $skipped[] = ['employee_id' => $employeeId, 'reason' => $e->getMessage()];
            }
        }

        return ['period' => $period, 'created' => $created, 'skipped' => $skipped];
    }

    public function updatePayroll(string $id, array $data)
    {
        $payroll = $this->getPayrollById($id);

        $period = $data['period'] ?? $payroll->period;

        $options = [
            'cash_advance_deduction' => array_key_exists('cash_advance_deduction', $data)
                ? $data['cash_advance_deduction']
                : $payroll->cash_advance_deduction,
        ];

        $calc = $this->calculateForEmployee($payroll->employee_id, $period, $options);

        return DB::transaction(function () use ($payroll, $calc, $data, $period) {
            $payload = [
                'period' => $period,
                'gross_share' => $calc['gross_share'],
                'ppn_rate' => $calc['ppn_rate'],
                'ppn_amount' => $calc['ppn_amount'],
                'commission_rate' => $calc['commission_rate'],
                'commission_amount' => $calc['commission_amount'],
                'meal_rate' => $calc['meal_rate'],
                'meal_count' => $calc['meal_count'],
                'meal_deduction' => $calc['meal_deduction'],
                'cash_advance_deduction' => $calc['cash_advance_deduction'],
                'net_salary' => $calc['net_salary'],
            ];

            if (isset($data['payment_status'])) {
                $payload['payment_status'] = $data['payment_status'];
            }
            if (isset($data['payment_date'])) {
                $payload['payment_date'] = $data['payment_date'];
            }

            $payroll->update($payload);

            $payroll->items()->delete();
            $this->storeItems($payroll, $calc['items']);

            return $payroll->fresh();
        });
    }

    protected function storeItems(Payroll $payroll, array $items): void
    {
        foreach ($items as $item) {
            $payroll->items()->create([
                'spk_id' => $item['spk_id'],
                'spk_number' => $item['spk_number'],
                'spk_value' => $item['spk_value'],
                'slot_count' => $item['slot_count'],
                'duration_days' => $item['duration_days'],
                'days_worked' => $item['days_worked'],
                'daily_rate' => $item['daily_rate'],
                'share_amount' => $item['share_amount'],
            ]);
        }
    }

    public function updatePaymentStatus(string $id, string $status, ?string $date = null)
    {
        $payroll = $this->getPayrollById($id);
        $paymentDate = ($status === 'Sudah Dibayar') ? ($date ?? date('Y-m-d')) : null;

        $updatedPayroll = $this->payrollRepository->update($id, [
            'payment_status' => $status,
            'payment_date' => $paymentDate,
        ]);

        if ($status === 'Sudah Dibayar') {
            $this->notifyPayrollPaid($updatedPayroll);
        }

        return $updatedPayroll;
    }

    /**
     * Notifikasi "slip terbit" ke karyawan (feed in-app + push FCM).
     */
    protected function notifyPayrollIssued($payroll): void
    {
        try {
            $user = $payroll->employee?->user;

            if (! $user) {
                return;
            }

            $this->notificationService->notify(
                $user,
                'Slip gaji '.$this->periodLabel($payroll->period).' sudah terbit',
                'Gaji bersih Rp '.number_format((float) $payroll->net_salary, 2, ',', '.').
                    ' dari '.$payroll->items()->count().' SPK — '.$payroll->slip_number,
                'payroll_issued',
                $payroll->id
            );
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi slip terbit: '.$e->getMessage());
        }
    }

    /**
     * Notifikasi "slip dibayar" ke karyawan.
     */
    protected function notifyPayrollPaid($payroll): void
    {
        try {
            $user = $payroll->employee?->user;

            if (! $user) {
                return;
            }

            $this->notificationService->notify(
                $user,
                'Slip gaji '.$this->periodLabel($payroll->period).' sudah dibayar',
                'Rp '.number_format((float) $payroll->net_salary, 2, ',', '.').
                    ' sudah ditransfer — '.$payroll->slip_number,
                'payroll_paid',
                $payroll->id
            );
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi slip dibayar: '.$e->getMessage());
        }
    }

    protected function periodLabel(?string $period): string
    {
        try {
            return Carbon::createFromFormat('Y-m', (string) $period)
                ->locale('id')
                ->translatedFormat('F Y');
        } catch (\Throwable $e) {
            return (string) $period;
        }
    }

    public function deletePayroll(string $id)
    {
        $payroll = $this->getPayrollById($id);

        return DB::transaction(function () use ($payroll) {
            $payroll->items()->delete();
            $this->payrollRepository->delete($payroll->id);

            return true;
        });
    }
}
