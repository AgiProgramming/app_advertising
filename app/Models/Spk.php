<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Spk extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'spk_number', 'client_name', 'date', 'start_date', 'end_date', 'duration_days',
        'shop_id', 'employee_id', 'job_name', 'location', 'description', 'spk_value',
        'job_status', 'payment_status', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_days' => 'integer',
        'spk_value' => 'decimal:2',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /** Penanggung jawab lama (dipertahankan untuk data historis). */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll()
    {
        return $this->hasOne(Payroll::class);
    }

    /** Semua penugasan (pekerja + pengganti). */
    public function assignments()
    {
        return $this->hasMany(SpkEmployee::class);
    }

    /** Hanya orang yang menempati slot pekerjaan (menentukan pembagi). */
    public function workers()
    {
        return $this->hasMany(SpkEmployee::class)->where('role', 'worker');
    }

    public function replacements()
    {
        return $this->hasMany(SpkEmployee::class)->where('role', 'replacement');
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    /** Jumlah orang yang menjadi pembagi nilai SPK. */
    public function slotCount(): int
    {
        return $this->assignments()->where('role', 'worker')->count();
    }

    /** Nilai satu hari kerja untuk satu orang. */
    public function dailyRate(): float
    {
        $slots = max(1, $this->slotCount());
        $days = max(1, (int) $this->duration_days);

        return round((float) $this->spk_value / ($slots * $days), 2);
    }
}
