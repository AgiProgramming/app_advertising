<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris rincian di dalam slip gaji: bagian seorang karyawan dari satu SPK.
 */
class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id', 'spk_id', 'spk_number', 'spk_value',
        'slot_count', 'duration_days', 'days_worked', 'daily_rate', 'share_amount',
    ];

    protected $casts = [
        'spk_value' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'share_amount' => 'decimal:2',
        'slot_count' => 'integer',
        'duration_days' => 'integer',
        'days_worked' => 'integer',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function spk()
    {
        return $this->belongsTo(Spk::class);
    }
}
