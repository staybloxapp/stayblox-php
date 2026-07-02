<?php

declare(strict_types=1);

namespace Stayblox\Distribution\Dto;

/** One date of an ARI push: rate, availability and restrictions. */
final class AriDate
{
    public function __construct(
        public readonly string $date,
        public readonly ?float $rate,
        public readonly int $availability,
        public readonly ?int $minStay,
        public readonly ?int $maxStay,
        public readonly bool $closedToArrival,
        public readonly bool $closedToDeparture,
        public readonly bool $stopSell,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromPayload(array $data): self
    {
        return new self(
            date: (string) $data['date'],
            rate: isset($data['rate']) ? (float) $data['rate'] : null,
            availability: (int) ($data['availability'] ?? 0),
            minStay: isset($data['min_stay']) ? (int) $data['min_stay'] : null,
            maxStay: isset($data['max_stay']) ? (int) $data['max_stay'] : null,
            closedToArrival: (bool) ($data['closed_to_arrival'] ?? false),
            closedToDeparture: (bool) ($data['closed_to_departure'] ?? false),
            stopSell: (bool) ($data['stop_sell'] ?? false),
        );
    }
}
