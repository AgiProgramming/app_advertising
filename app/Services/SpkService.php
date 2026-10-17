<?php
namespace App\Services;

use App\Repositories\Contracts\SpkRepositoryInterface;

class SpkService {
    protected $spkRepository;

    public function __construct(SpkRepositoryInterface $spkRepository) {
        $this->spkRepository = $spkRepository;
    }

    public function getAllSpks(?string $employeeId = null) {
        return $this->spkRepository->getAll($employeeId);
    }
    public function getSpkById(string $id) {
        return $this->spkRepository->getById($id);
    }
    public function createSpk(array $data) {
        return $this->spkRepository->create($data);
    }
    public function updateSpk(string $id, array $data) {
        return $this->spkRepository->update($id, $data);
    }
    public function deleteSpk(string $id) {
        return $this->spkRepository->delete($id);
    }
}