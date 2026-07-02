<?php

declare(strict_types=1);

namespace Stayblox\Distribution;

use Stayblox\Core\Api\DeveloperApiClient;
use Stayblox\Core\Installs\Install;
use Stayblox\Distribution\Dto\Reservation;
use Stayblox\Distribution\Dto\ReservationResult;

/**
 * The channel app->core half of the OTA protocol: register integrations and
 * listing links, submit externally-priced reservations, and pull listing
 * content and rate windows for backfill.
 */
class DistributionApiClient extends DeveloperApiClient
{
    private const INTEGRATION_FIELDS = 'id propertyId externalPropertyId status lastSyncedAt lastError
        listings { id unitTypeId externalRoomTypeId externalRatePlanId }';

    private const INTEGRATION_CREATE = 'mutation($input: ChannelIntegrationCreateInput!) {
        channelIntegrationCreate(input: $input) { integration { %s } userErrors { field message } }
    }';

    private const INTEGRATION_DISCONNECT = 'mutation($id: ID!) {
        channelIntegrationDisconnect(id: $id) { integration { %s } userErrors { field message } }
    }';

    private const INTEGRATIONS = 'query($first: Int, $after: ID) {
        channelIntegrations(first: $first, after: $after) {
            nodes { %s }
            pageInfo { hasNextPage endCursor }
        }
    }';

    private const LISTING_LINK = 'mutation($input: ChannelListingLinkInput!) {
        channelListingLink(input: $input) {
            listing { id unitTypeId externalRoomTypeId externalRatePlanId }
            userErrors { field message }
        }
    }';

    private const LISTING_UNLINK = 'mutation($id: ID!) {
        channelListingUnlink(id: $id) {
            listing { id unitTypeId externalRoomTypeId externalRatePlanId }
            userErrors { field message }
        }
    }';

    private const RESERVATION_UPSERT = 'mutation($input: ReservationInput!) {
        reservationUpsert(input: $input) {
            reservation { id externalId status bookingId }
            userErrors { field message }
        }
    }';

    private const PROPERTY_CONTENT = 'query($id: ID!) {
        property(id: $id) {
            id name type address city country countryCode lat lng active
            description cancellationPolicy checkInTime checkOutTime imageUrl
            facilities { name scope icon }
            unitTypes {
                id propertyId name maxGuests baseRate unitsCount active
                description occAdults occChildren occInfants photoUrls
                facilities { name scope icon }
                bedConfigurations { bedType quantity sleeps }
            }
        }
    }';

    private const UNIT_TYPE_RATES = 'query($unitTypeId: ID!, $from: String!, $to: String!) {
        unitTypeRates(unitTypeId: $unitTypeId, from: $from, to: $to) {
            date rate available minStay maxStay closedToArrival closedToDeparture stopSell
        }
    }';

    /**
     * @param  array<string, mixed>|null  $settings  encoded as a JSON string on
     *                                               the wire; the platform's
     *                                               `settings` input arg is a
     *                                               `String`, not a JSON scalar.
     * @return array{integration: ?array<string, mixed>, userErrors: list<array<string, mixed>>}
     */
    public function channelIntegrationCreate(Install $install, string $propertyId, string $externalPropertyId, ?array $settings = null): array
    {
        $input = array_filter([
            'propertyId' => $propertyId,
            'externalPropertyId' => $externalPropertyId,
            'settings' => $settings === null ? null : json_encode($settings, JSON_THROW_ON_ERROR),
        ], static fn ($value) => $value !== null);

        $data = $this->query($install, sprintf(self::INTEGRATION_CREATE, self::INTEGRATION_FIELDS), ['input' => $input]);

        return $this->result($data, 'channelIntegrationCreate', 'integration');
    }

    /** @return array{integration: ?array<string, mixed>, userErrors: list<array<string, mixed>>} */
    public function channelIntegrationDisconnect(Install $install, string $integrationId): array
    {
        $data = $this->query($install, sprintf(self::INTEGRATION_DISCONNECT, self::INTEGRATION_FIELDS), ['id' => $integrationId]);

        return $this->result($data, 'channelIntegrationDisconnect', 'integration');
    }

    /** @return array{nodes: list<array<string, mixed>>, pageInfo: array{hasNextPage: bool, endCursor: ?string}} */
    public function channelIntegrations(Install $install, int $first = 50, ?string $after = null): array
    {
        $data = $this->query($install, sprintf(self::INTEGRATIONS, self::INTEGRATION_FIELDS), array_filter([
            'first' => $first,
            'after' => $after,
        ], static fn ($value) => $value !== null));

        return $data['channelIntegrations'] ?? ['nodes' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]];
    }

    /** @return array{listing: ?array<string, mixed>, userErrors: list<array<string, mixed>>} */
    public function channelListingLink(Install $install, string $integrationId, string $unitTypeId, string $externalRoomTypeId, ?string $externalRatePlanId = null): array
    {
        $input = array_filter([
            'integrationId' => $integrationId,
            'unitTypeId' => $unitTypeId,
            'externalRoomTypeId' => $externalRoomTypeId,
            'externalRatePlanId' => $externalRatePlanId,
        ], static fn ($value) => $value !== null);

        $data = $this->query($install, self::LISTING_LINK, ['input' => $input]);

        return $this->result($data, 'channelListingLink', 'listing');
    }

    /** @return array{listing: ?array<string, mixed>, userErrors: list<array<string, mixed>>} */
    public function channelListingUnlink(Install $install, string $listingId): array
    {
        $data = $this->query($install, self::LISTING_UNLINK, ['id' => $listingId]);

        return $this->result($data, 'channelListingUnlink', 'listing');
    }

    public function reservationUpsert(Install $install, Reservation $reservation): ReservationResult
    {
        $data = $this->query($install, self::RESERVATION_UPSERT, ['input' => $reservation->toInput()]);
        $payload = $data['reservationUpsert'] ?? [];
        $node = $payload['reservation'] ?? null;

        return new ReservationResult(
            id: $node['id'] ?? null,
            externalId: $node['externalId'] ?? null,
            status: $node['status'] ?? null,
            bookingId: $node['bookingId'] ?? null,
            userErrors: $payload['userErrors'] ?? [],
        );
    }

    /** @return array<string, mixed> */
    public function propertyContent(Install $install, string $propertyId): array
    {
        $data = $this->query($install, self::PROPERTY_CONTENT, ['id' => $propertyId]);

        return $data['property'] ?? [];
    }

    /** @return list<array<string, mixed>> */
    public function unitTypeRates(Install $install, string $unitTypeId, string $from, string $to): array
    {
        $data = $this->query($install, self::UNIT_TYPE_RATES, [
            'unitTypeId' => $unitTypeId,
            'from' => $from,
            'to' => $to,
        ]);

        return $data['unitTypeRates'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0?: mixed}&array<string, mixed>
     */
    private function result(array $data, string $mutation, string $key): array
    {
        $payload = $data[$mutation] ?? [];

        return [
            $key => $payload[$key] ?? null,
            'userErrors' => $payload['userErrors'] ?? [],
        ];
    }
}
