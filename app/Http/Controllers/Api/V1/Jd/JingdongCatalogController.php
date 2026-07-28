<?php

namespace App\Http\Controllers\Api\V1\Jd;

use App\Exceptions\RapidApi\RapidApiException;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Elim\ProductDetailRequest;
use App\Http\Requests\Api\V1\Elim\ProductImageSearchRequest;
use App\Http\Requests\Api\V1\Elim\ProductImageUploadRequest;
use App\Http\Requests\Api\V1\Elim\ProductListRequest;
use App\Http\Requests\Api\V1\Elim\ProductSearchRequest;
use App\Http\Resources\Api\V1\Elim\CategoryListResource;
use App\Http\Resources\Api\V1\Elim\ProductDetailResource;
use App\Http\Resources\Api\V1\Elim\ProductListResource;
use App\Services\RapidApi\JingdongService;
use Illuminate\Http\JsonResponse;

class JingdongCatalogController extends ApiController
{
    public function __construct(private readonly JingdongService $jingdong)
    {
    }

    public function products(ProductListRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductListResource($this->jingdong->list($request->validated())))->resolve(), __('api.jd_products_listed'));
    }

    public function search(ProductSearchRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductListResource($this->jingdong->search($request->validated())))->resolve(), __('api.jd_products_listed'));
    }

    public function show(ProductDetailRequest $request, string $id): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductDetailResource($this->jingdong->find($id, $request->validated('lang'))))->resolve(), __('api.jd_product_fetched'));
    }

    public function categories(ProductDetailRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new CategoryListResource($this->jingdong->categories($request->validated('lang'))))->resolve(), __('api.jd_categories_listed'));
    }

    public function imageSearch(ProductImageSearchRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductListResource($this->jingdong->searchByImage($request->validated())))->resolve(), __('api.jd_products_listed'));
    }

    public function uploadImage(ProductImageUploadRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->jingdong->uploadImage($request->file('file')), __('api.jd_image_uploaded'));
    }

    private function respond(callable $callback, string $message): JsonResponse
    {
        try {
            return $this->successResponse($callback(), $message);
        } catch (RapidApiException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->context(), $exception->getCode() ?: 502);
        }
    }
}
