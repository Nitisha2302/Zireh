<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Lesson;
use App\Models\News;
use App\Services\FileManager;
use Illuminate\Http\JsonResponse;

class ContentController extends ApiController
{
    public function lessons(FileManager $fileManager): JsonResponse
    {
        $locale = app()->getLocale();
        $perPage = min(max((int) request('per_page', 15), 1), 50);

        $paginator = Lesson::query()
            ->where('is_active', true)
            ->latest()
            ->paginate($perPage);

        return $this->successResponse([
            'language' => $locale,
            'items' => $paginator->getCollection()
                ->map(fn (Lesson $lesson): array => $this->mapItem($lesson, $fileManager, $locale))
                ->values()
                ->all(),
            'pagination' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ], __('api.lessons_listed'))->header('Content-Language', $locale);
    }

    public function news(FileManager $fileManager): JsonResponse
    {
        $locale = app()->getLocale();
        $perPage = min(max((int) request('per_page', 15), 1), 50);

        $paginator = News::query()
            ->where('is_active', true)
            ->latest()
            ->paginate($perPage);

        return $this->successResponse([
            'language' => $locale,
            'items' => $paginator->getCollection()
                ->map(fn (News $item): array => $this->mapItem($item, $fileManager, $locale))
                ->values()
                ->all(),
            'pagination' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ], __('api.news_listed'))->header('Content-Language', $locale);
    }

    private function mapItem(Lesson|News $item, FileManager $fileManager, string $locale): array
    {
        return [
            'id' => $item->id,
            'title' => $item->getTranslation('title', $locale),
            'description' => $item->getTranslation('description', $locale),
            'image' => $fileManager->url($item->image),
            'status' => $item->is_active ? 'active' : 'inactive',
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }
}
