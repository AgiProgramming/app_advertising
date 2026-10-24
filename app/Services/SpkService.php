<?php

namespace App\Services;

use App\Models\Spk;
use App\Repositories\Contracts\SpkRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SpkService
{
    protected $spkRepository;

    public function __construct(SpkRepositoryInterface $spkRepository)
    {
        $this->spkRepository = $spkRepository;
    }

    public function getAllSpks(?string $employeeId = null)
    {
        return $this->spkRepository->getAll($employeeId);
    }

    public function getSpkById(string $id)
    {
        return $this->spkRepository->getById($id);
    }

    public function createSpk(array $data)
    {
        return DB::transaction(function () use ($data) {
            $members = $this->pullMembers($data);

            $data['duration_days'] = $this->resolveDuration($data);

            $spk = $this->spkRepository->create($data);

            $this->syncMembers($spk, $members);

            return $spk->fresh();
        });
    }

    public function updateSpk(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $members = $this->pullMembers($data);
            $hasMembers = $members !== null;

            $existing = $this->spkRepository->getById($id);

            $data['duration_days'] = $this->resolveDuration($data, $existing);

            $spk = $this->spkRepository->update($id, $data);

            if ($hasMembers) {
                $spk->assignments()->delete();
                $this->syncMembers($spk, $members);
            }

            return $spk->fresh();
        });
    }

    public function deleteSpk(string $id)
    {
        return $this->spkRepository->delete($id);
    }

    /**
     * Ambil daftar anggota dari payload (mendukung kunci "members" maupun "employees").
     * Mengembalikan null bila payload tidak menyertakan daftar anggota.
     */
    protected function pullMembers(array &$data): ?array
    {
        if (array_key_exists('members', $data)) {
            $members = $data['members'];
            unset($data['members'], $data['employees']);

            return is_array($members) ? $members : [];
        }

        if (array_key_exists('employees', $data)) {
            $members = $data['employees'];
            unset($data['employees']);

            return is_array($members) ? $members : [];
        }

        return null;
    }

    /**
     * Simpan penugasan: pekerja menempati slot, pengganti mengambil hari milik orang lain.
     */
    protected function syncMembers(Spk $spk, array $members): void
    {
        $duration = max(1, (int) $spk->duration_days);

        foreach ($members as $member) {
            if (empty($member['employee_id'])) {
                continue;
            }

            $role = $member['role'] ?? 'worker';
            $days = isset($member['days_worked']) ? (int) $member['days_worked'] : $duration;

            // Pekerja default masuk penuh; pengganti wajib menyebut jumlah hari.
            if ($role === 'worker' && $days <= 0) {
                $days = $duration;
            }

            $spk->assignments()->create([
                'employee_id' => $member['employee_id'],
                'role' => $role,
                'replacement_for_id' => $member['replacement_for_id'] ?? null,
                'days_worked' => max(0, min($days, $duration)),
            ]);
        }
    }

    /**
     * Durasi kerja dihitung dari tanggal mulai & selesai.
     */
    protected function resolveDuration(array $data, ?Spk $existing = null): int
    {
        $start = $data['start_date'] ?? optional($existing?->start_date)->toDateString();
        $end = $data['end_date'] ?? optional($existing?->end_date)->toDateString();

        if ($start && $end) {
            $days = Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;

            return max(1, (int) $days);
        }

        if (isset($data['duration_days'])) {
            return max(1, (int) $data['duration_days']);
        }

        return max(1, (int) ($existing->duration_days ?? 1));
    }
}
