<?php

namespace App\Services\RapidApi;

use App\Exceptions\RapidApi\RapidApiException;
use App\Services\Elim\Contracts\MarketplaceProductService;
use App\Services\PlatformCategoryService;
use App\Support\Elim\ProductNormalizer;
use App\Support\RapidApi\JdToElimMapper;
use App\Support\RapidApi\RapidApiConfig;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class JingdongService implements MarketplaceProductService
{
    public function __construct(
        protected readonly RapidApiClient $client,
        protected readonly JdToElimMapper $mapper,
        protected readonly ProductNormalizer $normalizer,
        protected readonly RapidApiConfig $config,
    ) {}

    public function platform(): string
    {
        return 'jd';
    }

    public function search(array $filters): array
    {
        $filters = $this->filtersWithCategory($filters);
        $keyword = (string) ($filters['q'] ?? '');
        $page = max(1, (int) ($filters['page'] ?? 1));

        if ($keyword === '') {
            $keyword = $this->config->defaultQuery();
        }

        $payload = [
            'keyword' => $keyword,
            'page' => $page,
        ];

        return Cache::remember($this->cacheKey('search', $payload), $this->productTtl(), function () use ($payload): array {
            $response = $this->client->get('/china-ecommerce/jd-search', $payload);
            $elimShaped = $this->mapper->toSearchResponse($response);

            return $this->normalizer->listResponse($elimShaped, $this->platform());
        });
    }

    public function list(array $filters): array
    {
        $filters = $this->filtersWithCategory($filters);

        return $this->search([
            ...$filters,
            'q' => $filters['q'] ?? $this->config->defaultQuery(),
        ]);
    }

    public function find(string $id, string|null $lang = null): array
    {
        $payload = ['itemId' => $id];

        return Cache::remember($this->cacheKey('detail', $payload), $this->productTtl(), function () use ($id): array {
            $detailEnvelope = $this->client->get('/china-ecommerce/jd-detail', ['itemId' => $id]);
            $detail = $this->mapper->firstDataItem($detailEnvelope);

            if ($detail === null) {
                throw new RapidApiException(__('api.jd_product_not_found'), 404, context: [
                    'product_id' => [$id],
                ]);
            }

            $priceEnvelope = $this->client->get('/china-ecommerce/jd-price', ['itemId' => $id]);
            $price = $this->mapper->firstDataItem($priceEnvelope);

            $elimShaped = $this->mapper->toDetailResponse($detail, $price);

            return $this->normalizer->detailResponse($elimShaped, $this->platform());
        });
    }

    public function categories(string|null $lang = null): array
    {
        return app(PlatformCategoryService::class)->listForPlatformKey($this->platform(), $lang);
    }

    public function searchByImage(array $filters): array
    {
        throw new RapidApiException(__('api.jd_image_search_unsupported'), 501, context: [
            'feature' => ['JD image search is not available via RapidAPI.'],
        ]);
    }

    public function uploadImage(UploadedFile $file): array
    {
        throw new RapidApiException(__('api.jd_image_upload_unsupported'), 501, context: [
            'feature' => ['JD image upload is not available via RapidAPI.'],
        ]);
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
            throw new RapidApiException(__('api.invalid_category_id'), 422, context: [
                'category_id' => [__('api.invalid_category_id')],
            ]);
        }

        unset($filters['category_id']);
        $filters['q'] = $keyword;

        return $filters;
    }

    protected function cacheKey(string $scope, array $payload): string
    {
        ksort($payload);

        return 'rapidapi:'.$this->config->credentialsFingerprint().':'.$this->platform().':'.$scope.':'.md5(json_encode($payload));
    }

    protected function productTtl(): int
    {
        return $this->config->productsCacheTtl();
    }
}
