<?php

namespace App\Services\Elim\Contracts;

use Illuminate\Http\UploadedFile;

interface MarketplaceProductService
{
    public function platform(): string;

    public function search(array $filters): array;

    public function list(array $filters): array;

    public function find(string $id, string|null $lang = null): array;

    public function categories(string|null $lang = null): array;

    public function searchByImage(array $filters): array;

    public function uploadImage(UploadedFile $file): array;
}
