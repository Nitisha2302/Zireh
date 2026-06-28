<?php

namespace App\Services\Elim;

use App\Exceptions\Elim\ElimException;
use App\Services\Elim\Contracts\MarketplaceProductService;
use App\Services\PlatformCategoryService;
use App\Support\Elim\ProductNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

abstract class AbstractElimProductService implements MarketplaceProductService
{
    public function __construct(
        protected readonly ElimApiClient $client,
        protected readonly ProductNormalizer $normalizer
    ) {
    }

    abstract public function platform(): string;

    public function search(array $filters): array
    {
        $filters = $this->filtersWithCategory($filters);
        $payload = $this->payload($filters);
        $cacheKey = $this->cacheKey('search', $payload);

        return Cache::remember($cacheKey, $this->productTtl(), function () use ($payload): array {
            $response = $this->client->post('/v1/products/search', $payload);

            return $this->normalizer->listResponse($response, $this->platform());
        });
    }

    public function list(array $filters): array
    {
        $filters = $this->filtersWithCategory($filters);

        return $this->search([
            ...$filters,
            'q' => $filters['q'] ?? config('services.elim.default_query', 'bag'),
        ]);
    }

    public function find(string $id, string|null $lang = null): array
    {
        $payload = [
            'id' => $id,
            'platform' => $this->elimPlatform(),
            'lang' => $this->lang($lang),
        ];

        return Cache::remember($this->cacheKey('detail', $payload), $this->productTtl(), function () use ($payload): array {
            $response = $this->client->post('/v1/products/find', $payload);

            return $this->normalizer->detailResponse($response, $this->platform());
        });
    }

    public function categories(string|null $lang = null): array
    {
        return app(PlatformCategoryService::class)->listForPlatformKey($this->platform(), $lang);
    }

    public function searchByImage(array $filters): array
    {
        $payload = $this->payload($filters);

        return Cache::remember($this->cacheKey('image-search', $payload), $this->productTtl(), function () use ($payload): array {
            $response = $this->client->post('/v1/products/search-img', $payload);

            return $this->normalizer->listResponse($response, $this->platform());
        });
    }

    public function uploadImage(UploadedFile $file): array
    {
        return $this->client->upload('/v1/products/upload-image', $file, [
            'platform' => $this->elimPlatform(),
        ]);
    }

    protected function payload(array $filters): array
    {
        return array_filter([
            'q' => $filters['q'] ?? null,
            'img_url' => $filters['img_url'] ?? null,
            'img_id' => $filters['img_id'] ?? null,
            'platform' => $this->elimPlatform(),
            'lang' => $this->lang($filters['lang'] ?? null),
            'sort' => $filters['sort'] ?? null,
            'filter' => $filters['filter'] ?? null,
            'page' => (int) ($filters['page'] ?? 1),
            'size' => (int) ($filters['size'] ?? 20),
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    protected function filtersWithCategory(array $filters): array
    {
        if (empty($filters['category_id'])) {
            unset($filters['category_id']);

            return $filters;
        }

        $keyword = app(PlatformCategoryService::class)->keywordForPlatform(
            $this->platform(),
            $filters['category_id']
        );

        if ($keyword === null) {
            throw new ElimException(__('api.invalid_category_id'), 422, context: [
                'category_id' => [__('api.invalid_category_id')],
            ]);
        }

        unset($filters['category_id']);
        $filters['q'] = $keyword;

        return $filters;
    }

    protected function lang(string|null $lang): string
    {
        return in_array($lang, ['vi', 'en'], true) ? $lang : (string) config('services.elim.default_lang', 'en');
    }

    protected function elimPlatform(): string
    {
        return $this->platform() === '1688' ? 'alibaba' : $this->platform();
    }

    protected function cacheKey(string $scope, array $payload): string
    {
        ksort($payload);

        return 'elim:'.$this->platform().':'.$scope.':'.md5(json_encode($payload));
    }

    protected function productTtl(): int
    {
        return (int) config('services.elim.cache.products_ttl', 900);
    }

    protected function categoryTtl(): int
    {
        return (int) config('services.elim.cache.categories_ttl', 86400);
    }
}
