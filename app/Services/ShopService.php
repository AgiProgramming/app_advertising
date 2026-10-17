<?php
namespace App\Services;

use App\Repositories\Contracts\ShopRepositoryInterface;

class ShopService {
    protected $shopRepository;

    public function __construct(ShopRepositoryInterface $shopRepository) {
        $this->shopRepository = $shopRepository;
    }

    public function getAllShops() {
        return $this->shopRepository->getAll();
    }
    public function getShopById(string $id) {
        return $this->shopRepository->getById($id);
    }
    public function createShop(array $data) {
        return $this->shopRepository->create($data);
    }
    public function updateShop(string $id, array $data) {
        return $this->shopRepository->update($id, $data);
    }
    public function deleteShop(string $id) {
        return $this->shopRepository->delete($id);
    }
}