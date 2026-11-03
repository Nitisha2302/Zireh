<?php

namespace App\Http\Controllers\Api\V1\Elim;

use App\Exceptions\Elim\ElimException;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Elim\ProductDetailRequest;
use App\Http\Requests\Api\V1\Elim\ProductImageSearchRequest;
use App\Http\Requests\Api\V1\Elim\ProductImageUploadRequest;
use App\Http\Requests\Api\V1\Elim\ProductListRequest;
use App\Http\Requests\Api\V1\Elim\ProductSearchRequest;
use App\Http\Resources\Api\V1\Elim\CategoryListResource;
use App\Http\Resources\Api\V1\Elim\ProductDetailResource;
use App\Http\Resources\Api\V1\Elim\ProductListResource;
use App\Services\Elim\TaobaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaobaoCatalogController extends ApiController
{
    public function __construct(private readonly TaobaoService $taobao) {}

    public function products(ProductListRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductListResource($this->taobao->list($request->validated())))->resolve(), __('api.elim_products_listed'));
    }

    public function search(ProductSearchRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductListResource($this->taobao->search($request->validated())))->resolve(), __('api.elim_products_listed'));
    }

    public function show(ProductDetailRequest $request, string $id): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductDetailResource($this->taobao->find($id, $request->validated('lang'))))->resolve(), __('api.elim_product_fetched'));
    }

    public function detail(ProductDetailRequest $request, string $id): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductDetailResource($this->taobao->detail($id, $request->validated('lang'))))->resolve(), __('api.elim_product_fetched'));
    }

    public function categories(ProductDetailRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new CategoryListResource($this->taobao->categories($request->validated('lang'))))->resolve(), __('api.elim_categories_listed'));
    }

    public function imageSearch(ProductImageSearchRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductListResource($this->taobao->searchByImage($request->validated())))->resolve(), __('api.elim_products_listed'));
    }

    public function uploadImage(ProductImageUploadRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->taobao->uploadImage($request->file('file')), __('api.elim_image_uploaded'));
    }

    public function shippingFee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:2048'],
            'addressInfo' => ['nullable', 'array'],
            'addressInfo.country' => ['nullable', 'string', 'max:10'],
            'addressInfo.state' => ['nullable', 'string', 'max:120'],
            'addressInfo.city' => ['nullable', 'string', 'max:120'],
            'addressInfo.district' => ['nullable', 'string', 'max:120'],
        ]);

        return $this->respond(
            fn (): array => $this->taobao->shippingFee($validated['id'], $validated['addressInfo'] ?? null),
            __('api.shipping_cost_calculated')
        );
    }

    public function unshorten(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'url', 'max:2048'],
        ]);

        return $this->respond(
            fn (): array => $this->taobao->unshorten($validated['url']),
            __('api.elim_product_fetched')
        );
    }

    private function respond(callable $callback, string $message): JsonResponse
    {
        try {
            return $this->successResponse($callback(), $message);
        } catch (ElimException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->context(), $exception->getCode() ?: 502);
        }
    }
}
