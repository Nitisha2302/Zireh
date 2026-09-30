<?php

namespace App\Support\Alif;

/**
 * The outcome of one Alif provider request.
 *
 * `errorMessage` and `alifPaymentId` exist only so the request logger can
 * correlate and explain the row; they are never serialized into the response.
 */
final readonly class AlifResult
{
    public function __construct(
        public AlifResponseCode $code,
        public mixed $id = null,
        public ?string $responseId = null,
        public ?Money $amount = null,
        public ?string $infoForClient = null,
        public ?string $errorMessage = null,
        public ?int $alifPaymentId = null,
    ) {}

    public static function failure(
        AlifResponseCode $code,
        mixed $id = null,
        ?string $errorMessage = null,
        ?int $alifPaymentId = null,
    ): self {
        return new self(
            code: $code,
            id: $id,
            errorMessage: $errorMessage,
            alifPaymentId: $alifPaymentId,
        );
    }

    /**
     * The JSON document Alif receives. Optional fields are omitted entirely
     * rather than sent as null, matching the documented examples.
     *
     * The `id` is echoed back exactly as Alif sent it so a numeric id stays
     * numeric and a string id stays a string.
     */
    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code->value,
            'id' => $this->id,
            'response_id' => $this->responseId,
            'amount' => $this->amount?->value(),
            'info_for_client' => $this->infoForClient,
        ], fn (mixed $value): bool => $value !== null);
    }
}
