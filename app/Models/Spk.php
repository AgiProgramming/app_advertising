<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Spk extends Model {
    use HasUuids, SoftDeletes;
    protected $fillable = ['spk_number', 'date', 'shop_id', 'employee_id', 'job_name', 'description', 'spk_value', 'job_status', 'payment_status', 'notes'];

    public function shop() {
        return $this->belongsTo(Shop::class);
    }
    public function employee() {
        return $this->belongsTo(Employee::class);
    }
    public function payroll() {
        return $this->hasOne(Payroll::class);
    }
}