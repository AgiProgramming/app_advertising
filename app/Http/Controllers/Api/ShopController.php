<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShopRequest;
use App\Http\Resources\ShopResource;
use App\Services\ShopService;

class ShopController extends Controller
{
    protected $shopService;

    public function __construct(ShopService $shopService)
    {
        $this->shopService = $shopService;
    }

    public function index()
    {
        $shops = $this->shopService->getAllShops();

        return ShopResource::collection($shops);
    }

    public function store(ShopRequest $request)
    {
        $shop = $this->shopService->createShop($request->validated());

        return new ShopResource($shop);
    }

    public function show(string $id)
    {
        $shop = $this->shopService->getShopById($id);

        return new ShopResource($shop);
    }

    public function update(ShopRequest $request, string $id)
    {
        $shop = $this->shopService->updateShop($id, $request->validated());

        return new ShopResource($shop);
    }

    public function destroy(string $id)
    {
        $this->shopService->deleteShop($id);

        return response()->json(['message' => 'Toko berhasil dihapus.']);
    }
}
