<?php

namespace App\Repositories\Eloquent;

use App\Models\Shop;
use App\Repositories\Contracts\ShopRepositoryInterface;

class ShopRepository implements ShopRepositoryInterface
{
    public function getAll()
    {
        return Shop::orderBy('created_at', 'desc')->get();
    }

    public function getById(string $id)
    {
        return Shop::findOrFail($id);
    }

    public function create(array $data)
    {
        return Shop::create($data);
    }

    public function update(string $id, array $data)
    {
        $shop = $this->getById($id);
        $shop->update($data);

        return $shop;
    }

    public function delete(string $id)
    {
        $shop = $this->getById($id);
        $shop->delete();

        return true;
    }
}
