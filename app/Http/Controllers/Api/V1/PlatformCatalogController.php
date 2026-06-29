<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Platform;
use App\Models\PlatformSlider;
use App\Services\FileManager;
use App\Services\PlatformCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;


class PlatformCatalogController extends ApiController
{
    private const CACHE_TTL_SECONDS = 3600;

    public function platforms(FileManager $fileManager): JsonResponse
    {
        $locale = app()->getLocale();

        $data = Cache::remember(
            "api:platform-catalog:platforms:{$locale}",
            self::CACHE_TTL_SECONDS,
            function () use ($locale, $fileManager): array {
                return Platform::query()
                    ->where('is_available', true)
                    ->with([
                        'sliders:id,heading,link,image',
                        'commissionSlabs' => fn ($query) => $query
                            ->where('is_active', true)
                            ->orderBy('min_amount'),
                    ])
                    ->orderBy('id')
                    ->get()
                    ->map(fn (Platform $platform): array => [
                        'id' => $platform->id,
                        'name' => $platform->getTranslation('name', $locale),
                        'logo' => $fileManager->url($platform->getTranslation('logo', $locale)),
                        'commission_slabs' => $platform->commissionSlabs->map(fn ($slab): array => [
                            'id' => $slab->id,
                            'min_amount' => (float) $slab->min_amount,
                            'max_amount' => $slab->max_amount !== null ? (float) $slab->max_amount : null,
                            'commission_percentage' => (float) $slab->commission_percentage,
                        ])->values()->all(),
                        'sliders' => $platform->sliders->map(fn (PlatformSlider $slider): array => [
                            'id' => $slider->id,
                            'heading' => $slider->heading,
                            'link' => $slider->link,
                            'image' => $fileManager->url($slider->image),
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all();
            }
        );

        return $this->successResponse(
            ['language' => $locale, 'platforms' => $data],
            __('api.platforms_listed')
        )->header('Content-Language', $locale);
    }

    public function commissionSlabs(Platform $platform, PlatformCommissionService $commissionService): JsonResponse
    {
        if (! $platform->is_available) {
            throw ValidationException::withMessages([
                'platform_id' => [__('api.platform_not_available')],
            ]);
        }

        return $this->successResponse(
            $commissionService->listForPlatform($platform->id),
            __('api.commission_slabs_listed')
        );
    }

    public function sliders(FileManager $fileManager): JsonResponse
    {
        $locale = app()->getLocale();
        $platformId = request('platform_id');
        $data = Cache::remember(
            "api:platform-catalog:sliders:{$locale}{$platformId}",
            self::CACHE_TTL_SECONDS,
            function () use ($locale, $fileManager, $platformId): array {
                return PlatformSlider::query()
                    ->when($platformId, fn($query, $platformId) => $query->whereHas('platforms', fn($query) => $query->where('id', $platformId)))
                    ->whereHas('platforms', fn($query) => $query->where('is_available', true))
                    ->with(['platforms' => fn($query) => $query->where('is_available', true)])
                    ->latest()
                    ->get()
                    ->map(fn(PlatformSlider $slider): array => [
                        'id' => $slider->id,
                        'heading' => $slider->heading,
                        'link' => $slider->link,
                        'image' => $fileManager->url($slider->image),
                        'platforms' => $slider->platforms->map(fn(Platform $platform): array => [
                            'id' => $platform->id,
                            'name' => $platform->getTranslation('name', $locale),
                            'logo' => $fileManager->url($platform->getTranslation('logo', $locale)),
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all();
            }
        );

        return $this->successResponse(
            ['language' => $locale, 'sliders' => $data],
            __('api.platform_sliders_listed')
        )->header('Content-Language', $locale);
    }
}
