<?php

declare(strict_types=1);

namespace Stayblox\Distribution\Dto;

/** The ARI window for one linked external room type / rate plan. */
final class AriListing
{
    /** @param list<AriDate> $dates */
    public function __construct(
        public readonly string $externalRoomTypeId,
        public readonly ?string $externalRatePlanId,
        public readonly array $dates,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromPayload(array $data): self
    {
        return new self(
            externalRoomTypeId: (string) $data['external_room_type_id'],
            externalRatePlanId: isset($data['external_rate_plan_id']) ? (string) $data['external_rate_plan_id'] : null,
            dates: array_map(
                static fn (array $date): AriDate => AriDate::fromPayload($date),
                array_values($data['dates'] ?? []),
            ),
        );
    }
}
