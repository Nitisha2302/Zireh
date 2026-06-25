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
use App\Services\Elim\Alibaba1688Service;
use Illuminate\Http\JsonResponse;

class Alibaba1688CatalogController extends ApiController
{
    public function __construct(private readonly Alibaba1688Service $alibaba1688)
    {
    }

    public function products(ProductListRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductListResource($this->alibaba1688->list($request->validated())))->resolve(), __('api.elim_products_listed'));
    }

    public function search(ProductSearchRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductListResource($this->alibaba1688->search($request->validated())))->resolve(), __('api.elim_products_listed'));
    }

    public function show(ProductDetailRequest $request, string $id): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductDetailResource($this->alibaba1688->find($id, $request->validated('lang'))))->resolve(), __('api.elim_product_fetched'));
    }

    public function categories(ProductDetailRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new CategoryListResource($this->alibaba1688->categories($request->validated('lang'))))->resolve(), __('api.elim_categories_listed'));
    }

    public function imageSearch(ProductImageSearchRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => (new ProductListResource($this->alibaba1688->searchByImage($request->validated())))->resolve(), __('api.elim_products_listed'));
    }

    public function uploadImage(ProductImageUploadRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->alibaba1688->uploadImage($request->file('file')), __('api.elim_image_uploaded'));
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
