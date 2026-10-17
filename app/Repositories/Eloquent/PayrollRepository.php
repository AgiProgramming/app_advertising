<?php
namespace App\Repositories\Eloquent;

use App\Models\Payroll;
use App\Repositories\Contracts\PayrollRepositoryInterface;

class PayrollRepository implements PayrollRepositoryInterface
{
    public function getAll(?string $employeeId = null, ?string $period = null, ?string $paymentStatus = null)
    {
        $query = Payroll::with(['employee', 'spk.shop'])->orderBy('created_at', 'desc');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($period) {
            $query->where('period', $period);
        }

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        return $query->get();
    }

    public function getById(string $id)
    {
        return Payroll::with(['employee', 'spk.shop'])->findOrFail($id);
    }

    public function getBySpkId(string $spkId)
    {
        return Payroll::with(['employee', 'spk.shop'])->where('spk_id', $spkId)->first();
    }

    public function create(array $data)
    {
        if (empty($data['slip_number'])) {
            $data['slip_number'] = $this->generateSlipNumber();
        }
        return Payroll::create($data);
    }

    public function update(string $id, array $data)
    {
        $payroll = $this->getById($id);
        $payroll->update($data);
        return $payroll;
    }

    public function delete(string $id)
    {
        $payroll = $this->getById($id);
        $payroll->delete();
        return true;
    }

    public function generateSlipNumber(): string
    {
        $datePrefix = date('Ymd');
        $lastPayroll = Payroll::withTrashed()->where('slip_number', 'LIKE', "SLIP-{$datePrefix}-%")
            ->orderBy('slip_number', 'desc')
            ->first();

        if (!$lastPayroll) {
            return "SLIP-{$datePrefix}-001";
        }

        $lastNumber = intval(substr($lastPayroll->slip_number, -3));
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return "SLIP-{$datePrefix}-{$newNumber}";
    }

    public function getTotalCashAdvanceDeducted(string $employeeId): float
    {
        return (float) Payroll::where('employee_id', $employeeId)->sum('cash_advance_deduction');
    }

    public function getTotalPaidSalary(?string $period = null): float
    {
        $query = Payroll::where('payment_status', 'Sudah Dibayar');
        if ($period) {
            $query->where('period', $period);
        }
        return (float) $query->sum('net_salary');
    }
}
