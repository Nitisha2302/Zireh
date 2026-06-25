<?php

namespace App\Services\Elim;

class TaobaoService extends AbstractElimProductService
{
    public function platform(): string
    {
        return 'taobao';
    }
}
