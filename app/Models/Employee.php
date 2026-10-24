<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['user_id', 'nik', 'name', 'position', 'phone', 'address', 'is_active'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashAdvances()
    {
        return $this->hasMany(CashAdvance::class);
    }

    public function spks()
    {
        return $this->hasMany(Spk::class);
    }

    /** Penugasan pada SPK (sebagai pekerja maupun pengganti). */
    public function spkAssignments()
    {
        return $this->hasMany(SpkEmployee::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
}
