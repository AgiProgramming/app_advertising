<?php
namespace App\Repositories\Eloquent;

use App\Models\Spk;
use App\Repositories\Contracts\SpkRepositoryInterface;

class SpkRepository implements SpkRepositoryInterface {
    public function getAll(?string $employeeId = null) {
        $query = Spk::with(['shop', 'employee'])->orderBy('created_at', 'desc');
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        return $query->get();
    }
    public function getById(string $id) {
        return Spk::with(['shop', 'employee'])->findOrFail($id);
    }
    public function create(array $data) {
        $data['spk_number'] = $this->generateSpkNumber();
        return Spk::create($data);
    }
    public function update(string $id, array $data) {
        $spk = $this->getById($id);
        $spk->update($data);
        return $spk;
    }
    public function delete(string $id) {
        $spk = $this->getById($id);
        $spk->delete();
        return true;
    }
    
    // Logika pembuatan SPK Otomatis
    public function generateSpkNumber() {
        $datePrefix = date('Ymd');
        $lastSpk = Spk::withTrashed()->where('spk_number', 'LIKE', "SPK-{$datePrefix}-%")->orderBy('spk_number', 'desc')->first();
        
        if (!$lastSpk) {
            return "SPK-{$datePrefix}-001";
        }
        
        $lastNumber = intval(substr($lastSpk->spk_number, -3));
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        
        return "SPK-{$datePrefix}-{$newNumber}";
    }
}