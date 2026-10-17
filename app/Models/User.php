<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = ['role_id', 'username', 'password'];
    protected $hidden = ['password', 'remember_token'];

    public function role() {
        return $this->belongsTo(Role::class);
    }
    public function employee() {
        return $this->hasOne(Employee::class);
    }

    public function deviceTokens() {
        return $this->hasMany(DeviceToken::class);
    }

    public function appNotifications() {
        return $this->hasMany(AppNotification::class);
    }
}