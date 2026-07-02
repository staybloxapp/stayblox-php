<?php

declare(strict_types=1);

namespace Stayblox\Distribution\Dto;

/** Result of reservationUpsert. */
final class ReservationResult
{
    /** @param list<array{field: ?array<int, string>, message: string}> $userErrors */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $externalId,
        public readonly ?string $status,
        public readonly ?string $bookingId,
        public readonly array $userErrors = [],
    ) {}

    public function ok(): bool
    {
        return $this->userErrors === [] && $this->id !== null;
    }
}
