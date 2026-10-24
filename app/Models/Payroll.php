<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'slip_number', 'employee_id', 'spk_id', 'period',
        'gross_share', 'ppn_rate', 'ppn_amount',
        'commission_rate', 'commission_amount',
        'meal_rate', 'meal_count', 'meal_deduction',
        'original_spk_value', 'net_spk_value', 'salary_percentage', 'meal_allowance',
        'cash_advance_deduction', 'net_salary',
        'payment_status', 'payment_date', 'previous_job_status', 'verification_token',
    ];

    protected $casts = [
        'gross_share' => 'decimal:2',
        'ppn_rate' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'meal_rate' => 'decimal:2',
        'meal_count' => 'integer',
        'meal_deduction' => 'decimal:2',
        'cash_advance_deduction' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /** SPK tunggal (hanya untuk slip lama sebelum model multi-orang). */
    public function spk()
    {
        return $this->belongsTo(Spk::class);
    }

    /** Rincian bagian dari tiap SPK pada periode ini. */
    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }
}
