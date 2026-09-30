<?php

namespace App\Http\Responses;

use App\Support\Alif\AlifRequestContext;
use App\Support\Alif\AlifResult;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AlifResponse
{
    /**
     * Alif reads the outcome from the JSON `code` field, so every answer is
     * served with HTTP 200. A real HTTP 401 or 500 would look to Alif like a
     * transport failure rather than a protocol response.
     *
     * Every Alif answer is funnelled through here, which makes it the right
     * place to hand the full result to the request logger.
     */
    public static function make(AlifResult $result): JsonResponse
    {
        app(AlifRequestContext::class)->setResult($result);

        return response()->json(
            $result->toArray(),
            Response::HTTP_OK,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
