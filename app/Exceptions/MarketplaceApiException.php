<?php

namespace App\Exceptions;

use RuntimeException;

class MarketplaceApiException extends RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        string $message,
        public readonly string $marketplace,
        public readonly array $details = [],
        public readonly int $status = 502,
    ) {
        parent::__construct($message);
    }
}
