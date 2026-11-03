<?php

namespace App\Services\Elim;

use App\Exceptions\Elim\ElimException;
use App\Services\Elim\Contracts\MarketplaceProductService;
use App\Services\PlatformCategoryService;
use App\Support\Elim\ElimApiConfig;
use App\Support\Elim\ProductNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

abstract class AbstractElimProductService implements MarketplaceProductService
{
    public function __construct(
        protected readonly ElimApiClient $client,
        protected readonly ProductNormalizer $normalizer,
        protected readonly ElimApiConfig $config,
    ) {}

    abstract public function platform(): string;

    public function search(array $filters): array
    {
        $filters = $this->filtersWithCategory($filters);
        $payload = $this->payload($filters);
        $cacheKey = $this->cacheKey('search', $payload);

        return Cache::remember($cacheKey, $this->productTtl(), function () use ($payload): array {
            $uri = $this->client->resolveUriWithLegacyFallback('/v2/products/search', '/v1/products/search');
            $response = $this->client->post($uri, $payload);

            return $this->normalizer->listResponse($response, $this->platform());
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

    public function find(string $id, ?string $lang = null): array
    {
        $payload = [
            'id' => $this->findIdentifier($id),
            'platform' => $this->elimPlatform(),
            'lang' => $this->lang($lang),
        ];

        return Cache::remember($this->cacheKey('detail', $payload), $this->productTtl(), function () use ($payload): array {
            $uri = $this->client->resolveUriWithLegacyFallback('/v2/products/find', '/v1/products/find');
            $response = $this->client->post($uri, $payload);

            return $this->normalizer->detailResponse($response, $this->platform());
        });
    }

    public function detail(string $id, ?string $lang = null): array
    {
        $payload = [
            'id' => $this->findIdentifier($id),
            'platform' => $this->elimPlatform(),
            'lang' => $this->lang($lang),
        ];

        return Cache::remember($this->cacheKey('full-detail', $payload), $this->productTtl(), function () use ($payload): array {
            $uri = $this->client->resolveUriWithLegacyFallback('/v2/products/detail', '/v1/products/detail');

            try {
                $response = $this->client->post($uri, $payload);
            } catch (ElimException) {
                $fallbackUri = $this->client->resolveUriWithLegacyFallback('/v2/products/find', '/v1/products/find');
                $response = $this->client->post($fallbackUri, $payload);
            }

            if ($response === []) {
                $fallbackUri = $this->client->resolveUriWithLegacyFallback('/v2/products/find', '/v1/products/find');
                $response = $this->client->post($fallbackUri, $payload);
            }

            return $this->normalizer->detailResponse($response, $this->platform());
        });
    }

    public function unshorten(string $url): array
    {
        $response = $this->client->get('/v1/utils/unshorten', [
            'url' => trim($url),
        ]);

        return is_array($response['data'] ?? null) ? $response['data'] : $response;
    }

    public function categories(?string $lang = null): array
    {
        return app(PlatformCategoryService::class)->listForPlatformKey($this->platform(), $lang);
    }

    public function searchByImage(array $filters): array
    {
        if (
            empty($filters['img_id'])
            && empty($filters['imgId'])
            && empty($filters['imageHandle'])
            && empty($filters['image_handle'])
            && empty($filters['img_url'])
            && empty($filters['imgUrl'])
            && isset($filters['file'])
            && $filters['file'] instanceof UploadedFile
        ) {
            $uploaded = $this->uploadImage($filters['file']);
            $filters['img_id'] = (string) ($uploaded['imageId'] ?? $uploaded['img_id'] ?? '');
            if (! empty($uploaded['imageHandle'])) {
                $filters['imageHandle'] = (string) $uploaded['imageHandle'];
            }
        }

        unset($filters['file']);

        $payload = $this->imageSearchPayload($filters);

        return Cache::remember($this->cacheKey('image-search', $payload), $this->productTtl(), function () use ($payload): array {
            $uri = $this->client->resolveUriWithLegacyFallback('/v2/products/search-by-image', '/v1/products/search-by-image');
            $response = $this->client->post($uri, $payload);

            return $this->normalizer->listResponse($response, $this->platform());
        });
    }

    public function uploadImage(UploadedFile $file): array
    {
        $uri = $this->client->resolveUriWithLegacyFallback('/v2/products/upload-image', '/v1/products/upload-image');
        $response = $this->client->upload($uri, $file, [
            'platform' => $this->elimPlatform(),
        ]);

        $imageId = $response['imageId']
            ?? $response['img_id']
            ?? $response['image_id']
            ?? $response['data']['imageId']
            ?? $response['data']['img_id']
            ?? $response['imageHandle']
            ?? $response['data']['imageHandle']
            ?? null;

        $imageHandle = $response['imageHandle']
            ?? $response['image_handle']
            ?? $response['data']['imageHandle']
            ?? $response['data']['image_handle']
            ?? null;

        if (is_string($imageId) && $imageId !== '') {
            $response['imageId'] = $imageId;
            $response['img_id'] = $imageId;
        }

        if (is_string($imageHandle) && $imageHandle !== '') {
            $response['imageHandle'] = $imageHandle;
            $response['image_handle'] = $imageHandle;
        }

        return $response;
    }

    protected function payload(array $filters): array
    {
        $keywordTranslate = $filters['keywordTranslate'] ?? $filters['keyword_translate'] ?? null;

        return array_filter([
            'q' => $filters['q'] ?? null,
            'img_url' => $filters['img_url'] ?? null,
            'img_id' => $filters['img_id'] ?? null,
            'platform' => $this->elimPlatform(),
            'lang' => $this->lang($filters['lang'] ?? null),
            'sort' => $filters['sort'] ?? null,
            'filter' => $this->normalizeSearchFilter($filters['filter'] ?? null),
            'page' => (int) ($filters['page'] ?? 1),
            'size' => (int) ($filters['size'] ?? 20),
            'keywordTranslate' => is_bool($keywordTranslate) ? $keywordTranslate : null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    protected function imageSearchPayload(array $filters): array
    {
        $imgUrl = $filters['imgUrl'] ?? $filters['img_url'] ?? null;
        $imgId = $filters['imgId'] ?? $filters['img_id'] ?? $filters['imageId'] ?? null;
        $imageHandle = $filters['imageHandle'] ?? $filters['image_handle'] ?? null;
        $keywordTranslate = $filters['keywordTranslate'] ?? $filters['keyword_translate'] ?? null;

        return array_filter([
            'platform' => $this->elimPlatform(),
            'imgUrl' => $imgUrl,
            'imgId' => $imgUrl ? null : $imgId,
            'imageHandle' => $imgUrl ? null : $imageHandle,
            'lang' => $this->lang($filters['lang'] ?? null),
            'sort' => $filters['sort'] ?? null,
            'filter' => $this->normalizeSearchFilter($filters['filter'] ?? null),
            'page' => (int) ($filters['page'] ?? 1),
            'size' => min(20, max(1, (int) ($filters['size'] ?? 20))),
            'keyword' => $filters['keyword'] ?? $filters['q'] ?? null,
            'keywordTranslate' => is_bool($keywordTranslate) ? $keywordTranslate : null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    protected function normalizeSearchFilter(mixed $filter): ?array
    {
        if (! is_array($filter) || $filter === []) {
            return null;
        }

        $keyMap = [
            'price_range' => 'priceRange',
            'shop_id' => 'shopId',
            'seller_level' => 'sellerLevel',
            'is_factory' => 'isFactory',
            'ffm_outbound' => 'ffmOutbound',
            'ffm_delivery' => 'ffmDelivery',
            'allow_return' => 'allowReturn',
            'allow_dropship' => 'allowDropship',
            'freeship_for_dropship' => 'freeshipForDropship',
            'new_arrival' => 'newArrival',
            'platform_pick' => 'platformPick',
            'global_pick' => 'globalPick',
            'dropship_pick' => 'dropshipPick',
            'category_id' => 'categoryId',
            'category_id_list' => 'categoryIdList',
            'seller_open_id' => 'sellerOpenId',
        ];

        $normalized = [];

        foreach ($filter as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($value === 'true' || $value === 'false') {
                $value = $value === 'true';
            }

            $mappedKey = $keyMap[$key] ?? $key;
            $normalized[$mappedKey] = $value;
        }

        return $normalized !== [] ? $normalized : null;
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

    protected function lang(?string $lang): string
    {
        return in_array($lang, ['vi', 'en', 'ru', 'ko', 'ja'], true) ? $lang : $this->config->defaultLang();
    }

    protected function elimPlatform(): string
    {
        return $this->platform() === '1688' ? 'alibaba' : $this->platform();
    }

    protected function findIdentifier(string $id): string
    {
        return trim(urldecode($id));
    }

    protected function cacheKey(string $scope, array $payload): string
    {
        ksort($payload);

        return 'elim:'.$this->config->credentialsFingerprint().':'.$this->platform().':'.$scope.':'.md5(json_encode($payload));
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
