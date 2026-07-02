<?php

declare(strict_types=1);

namespace Stayblox\Distribution\Dto;

/**
 * A normalized OTA reservation to submit via reservationUpsert. Status is the
 * GraphQL enum value: NEW, MODIFIED or CANCELLED; paymentCollect is OTA or
 * PROPERTY.
 */
final class Reservation
{
    /** @param list<ReservationRoom> $rooms */
    public function __construct(
        public readonly string $integrationId,
        public readonly string $externalId,
        public readonly string $revisionId,
        public readonly string $status,
        public readonly string $checkIn,
        public readonly string $checkOut,
        public readonly string $currency,
        public readonly string $paymentCollect,
        public readonly string $guestFirstName,
        public readonly string $guestLastName,
        public readonly float $totalAmount,
        public readonly array $rooms,
        public readonly ?string $guestEmail = null,
        public readonly ?string $guestPhone = null,
        public readonly ?string $externalPaymentId = null,
    ) {}

    /** @return array<string, mixed> */
    public function toInput(): array
    {
        return array_filter([
            'integrationId' => $this->integrationId,
            'externalId' => $this->externalId,
            'revisionId' => $this->revisionId,
            'status' => $this->status,
            'checkIn' => $this->checkIn,
            'checkOut' => $this->checkOut,
            'currency' => $this->currency,
            'paymentCollect' => $this->paymentCollect,
            'externalPaymentId' => $this->externalPaymentId,
            'guest' => array_filter([
                'firstName' => $this->guestFirstName,
                'lastName' => $this->guestLastName,
                'email' => $this->guestEmail,
                'phone' => $this->guestPhone,
            ], static fn ($value) => $value !== null),
            'totalAmount' => $this->totalAmount,
            'rooms' => array_map(static fn (ReservationRoom $room): array => $room->toInput(), $this->rooms),
        ], static fn ($value) => $value !== null);
    }
}
