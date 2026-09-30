<?php

namespace App\Support\Alif;

/**
 * Request-scoped hand-off between the layer that produces an Alif answer and
 * the middleware that logs it.
 *
 * The serialized response only carries the protocol fields, so without this
 * the logger would lose the internal error message and the payment row the
 * result belongs to.
 */
class AlifRequestContext
{
    private ?AlifResult $result = null;

    public function setResult(AlifResult $result): void
    {
        $this->result = $result;
    }

    public function result(): ?AlifResult
    {
        return $this->result;
    }
}
