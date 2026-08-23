<?php

namespace App\Services;

use App\Exceptions\DigisellerException;
use App\Exceptions\MarketplaceApiException;
use InvalidArgumentException;

class MarketplaceOptionSyncService
{
    public function __construct(private readonly DigisellerService $digiseller) {}

    /** @return list<string> */
    public function supportedMarketplaces(): array
    {
        return ['plati'];
    }

    /** @param array{name: string, modifier_type: string, rate: float} $option */
    public function update(
        string $marketplace,
        int $marketplaceParameterId,
        int $marketplaceOptionId,
        array $option,
    ): void {
        try {
            match ($marketplace) {
                'plati' => $this->digiseller->updateProductParameterVariant(
                    $marketplaceParameterId,
                    $marketplaceOptionId,
                    $option,
                ),
                default => throw new InvalidArgumentException("Marketplace [{$marketplace}] is not supported."),
            };
        } catch (DigisellerException $exception) {
            throw new MarketplaceApiException(
                $exception->getMessage(),
                $marketplace,
                $exception->details,
                $exception->status,
            );
        }
    }

    public function delete(string $marketplace, int $marketplaceParameterId, int $marketplaceOptionId): void
    {
        try {
            match ($marketplace) {
                'plati' => $this->digiseller->deleteProductParameterVariant(
                    $marketplaceParameterId,
                    $marketplaceOptionId,
                ),
                default => throw new InvalidArgumentException("Marketplace [{$marketplace}] is not supported."),
            };
        } catch (DigisellerException $exception) {
            throw new MarketplaceApiException(
                $exception->getMessage(),
                $marketplace,
                $exception->details,
                $exception->status,
            );
        }
    }
}
