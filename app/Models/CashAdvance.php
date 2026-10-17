<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashAdvance extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['employee_id', 'amount', 'date', 'description'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
