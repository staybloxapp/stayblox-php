<?php

declare(strict_types=1);

namespace Stayblox\Distribution\Dto;

/** A verified ari_push command from Stayblox core. */
final class AriPushCommand
{
    /** @param list<AriListing> $listings */
    public function __construct(
        public readonly ?string $externalPropertyId,
        public readonly array $listings,
        public readonly ?string $apiBaseUrl,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromPayload(array $payload): self
    {
        return new self(
            externalPropertyId: isset($payload['external_property_id']) ? (string) $payload['external_property_id'] : null,
            listings: array_map(
                static fn (array $listing): AriListing => AriListing::fromPayload($listing),
                array_values($payload['listings'] ?? []),
            ),
            apiBaseUrl: isset($payload['api_base_url']) ? (string) $payload['api_base_url'] : null,
        );
    }
}
