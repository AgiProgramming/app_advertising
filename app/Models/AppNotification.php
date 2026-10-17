<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasUuids;

    protected $table = 'app_notifications';

    protected $fillable = ['user_id', 'title', 'body', 'type', 'payroll_id', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
