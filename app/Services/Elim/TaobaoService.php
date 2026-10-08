<?php

namespace App\Services\Elim;

class TaobaoService extends AbstractElimProductService
{
    public function platform(): string
    {
        return 'taobao';
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
