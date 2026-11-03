<?php

namespace App\Services\Elim;

class Alibaba1688Service extends AbstractElimProductService
{
    public function platform(): string
    {
        return '1688';
    }

    protected function findIdentifier(string $id): string
    {
        $id = parent::findIdentifier($id);

        if (preg_match('~(?:offer/|offerId=)(\d+)~i', $id, $matches)) {
            return $matches[1];
        }

        return $id;
    }
}
