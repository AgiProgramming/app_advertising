<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Penugasan seorang karyawan pada satu SPK.
 *
 * role = worker      → orang yang menempati slot pekerjaan
 * role = replacement → orang pengganti, mengambil hari kerja milik orang lain
 */
class SpkEmployee extends Model
{
    protected $table = 'spk_employees';

    protected $fillable = ['spk_id', 'employee_id', 'role', 'replacement_for_id', 'days_worked'];

    protected $casts = [
        'days_worked' => 'integer',
    ];

    public function spk()
    {
        return $this->belongsTo(Spk::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /** Orang yang digantikan (khusus role = replacement). */
    public function replacedEmployee()
    {
        return $this->belongsTo(Employee::class, 'replacement_for_id');
    }

    public function isReplacement(): bool
    {
        return $this->role === 'replacement';
    }
}
