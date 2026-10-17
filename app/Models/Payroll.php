<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model {
    use HasUuids, SoftDeletes;
    protected $fillable = ['slip_number', 'employee_id', 'spk_id', 'period', 'original_spk_value', 'net_spk_value', 'salary_percentage', 'meal_allowance', 'cash_advance_deduction', 'net_salary', 'payment_status', 'payment_date', 'previous_job_status'];

    public function employee() {
        return $this->belongsTo(Employee::class);
    }
    public function spk() {
        return $this->belongsTo(Spk::class);
    }
}