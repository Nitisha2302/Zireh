<?php

namespace App\Services\Elim;

use App\Support\Elim\ElimWarehouseAddress;

class TaobaoService extends AbstractElimProductService
{
    public function platform(): string
    {
        return 'taobao';
    }

    public function shippingFee(string $id, ?array $addressInfo = null): array
    {
        if ($addressInfo === null || $addressInfo === []) {
            $warehouse = ElimWarehouseAddress::get();
            $addressInfo = array_filter([
                'country' => 'CN',
                'state' => $warehouse['province'] ?? null,
                'city' => $warehouse['city'] ?? null,
                'district' => $warehouse['area'] ?? null,
            ], fn (mixed $v): bool => $v !== null && $v !== '');
        }

        $response = $this->client->post('/v2/products/shipping-fee', [
            'platform' => 'taobao',
            'id' => $this->findIdentifier($id),
            'addressInfo' => $addressInfo,
        ]);

        return is_array($response['data'] ?? null) ? $response['data'] : $response;
    }

    protected function findIdentifier(string $id): string
    {
        $id = parent::findIdentifier($id);

        if ($id !== '' && ctype_digit($id)) {
            return 'https://item.taobao.com/item.htm?id='.$id;
        }

        return $id;
    }
}
