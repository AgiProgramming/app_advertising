<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model {
    use HasUuids, SoftDeletes;
    protected $fillable = ['code', 'name', 'owner_name', 'phone', 'address', 'status'];

    public function spks() {
        return $this->hasMany(Spk::class);
    }
}