<?php

declare(strict_types=1);

namespace Stayblox\Distribution\Dto;

/** One room of an OTA reservation with channel-priced nights. */
final class ReservationRoom
{
    /** @param list<array{date: string, price: float}> $nights */
    public function __construct(
        public readonly string $externalRoomTypeId,
        public readonly ?string $externalRatePlanId,
        public readonly int $adults,
        public readonly int $children,
        public readonly array $nights,
    ) {}

    /** @return array<string, mixed> */
    public function toInput(): array
    {
        return array_filter([
            'externalRoomTypeId' => $this->externalRoomTypeId,
            'externalRatePlanId' => $this->externalRatePlanId,
            'adults' => $this->adults,
            'children' => $this->children,
            'nights' => $this->nights,
        ], static fn ($value) => $value !== null);
    }
}
