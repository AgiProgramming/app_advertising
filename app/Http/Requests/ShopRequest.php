<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Dihandle oleh middleware role
    }

    public function rules(): array
    {
        $shopId = $this->route('shop') ? $this->route('shop') : 'NULL';

        return [
            'code' => 'required|string|max:20|unique:shops,code,'.$shopId,
            'name' => 'required|string|max:100',
            'owner_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:Aktif,Nonaktif',
        ];
    }
}
