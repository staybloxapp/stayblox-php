<?php

declare(strict_types=1);

namespace Stayblox\Tests\Distribution;

use Illuminate\Http\Client\Factory;
use Stayblox\Core\Installs\Install;
use Stayblox\Distribution\DistributionApiClient;
use Stayblox\Distribution\Dto\Reservation;
use Stayblox\Distribution\Dto\ReservationRoom;
use Stayblox\Tests\TestCase;

class DistributionApiClientTest extends TestCase
{
    private const URL = 'https://admin.stayblox.com/developer/api/2026-01/graphql';

    private function install(): Install
    {
        return new Install(['team_slug' => 'acme', 'access_token' => 'tok', 'webhook_secret' => 'x']);
    }

    public function test_channel_integration_create_sends_input(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response([
            'data' => ['channelIntegrationCreate' => [
                'integration' => ['id' => '5', 'propertyId' => '9', 'externalPropertyId' => 'host-1', 'status' => 'active', 'listings' => []],
                'userErrors' => [],
            ]],
        ])]);

        $result = (new DistributionApiClient(self::URL, $http))
            ->channelIntegrationCreate($this->install(), '9', 'host-1');

        $this->assertSame('5', $result['integration']['id']);
        $this->assertSame([], $result['userErrors']);
        $http->assertSent(fn ($request) => str_contains($request['query'], 'channelIntegrationCreate')
            && $request['variables']['input']['propertyId'] === '9'
            && $request['variables']['input']['externalPropertyId'] === 'host-1');
    }

    public function test_channel_integration_create_json_encodes_settings(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response([
            'data' => ['channelIntegrationCreate' => [
                'integration' => ['id' => '5', 'propertyId' => '9', 'externalPropertyId' => 'host-1', 'status' => 'active', 'listings' => []],
                'userErrors' => [],
            ]],
        ])]);

        (new DistributionApiClient(self::URL, $http))
            ->channelIntegrationCreate($this->install(), '9', 'host-1', ['syncFrequencyMinutes' => 15]);

        $http->assertSent(fn ($request) => $request['variables']['input']['settings'] === '{"syncFrequencyMinutes":15}');
    }

    public function test_channel_integration_create_omits_settings_when_null(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response([
            'data' => ['channelIntegrationCreate' => [
                'integration' => ['id' => '5', 'propertyId' => '9', 'externalPropertyId' => 'host-1', 'status' => 'active', 'listings' => []],
                'userErrors' => [],
            ]],
        ])]);

        (new DistributionApiClient(self::URL, $http))
            ->channelIntegrationCreate($this->install(), '9', 'host-1');

        $http->assertSent(fn ($request) => ! array_key_exists('settings', $request['variables']['input']));
    }

    public function test_channel_listing_link_sends_input(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response([
            'data' => ['channelListingLink' => [
                'listing' => ['id' => '3', 'unitTypeId' => '11', 'externalRoomTypeId' => 'listing-7', 'externalRatePlanId' => null],
                'userErrors' => [],
            ]],
        ])]);

        $result = (new DistributionApiClient(self::URL, $http))
            ->channelListingLink($this->install(), '5', '11', 'listing-7');

        $this->assertSame('3', $result['listing']['id']);
        $http->assertSent(fn ($request) => $request['variables']['input']['integrationId'] === '5'
            && $request['variables']['input']['unitTypeId'] === '11'
            && $request['variables']['input']['externalRoomTypeId'] === 'listing-7');
    }

    public function test_reservation_upsert_parses_result(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response([
            'data' => ['reservationUpsert' => [
                'reservation' => ['id' => '77', 'externalId' => 'ABJQXKRZ55', 'status' => 'synced', 'bookingId' => '123'],
                'userErrors' => [],
            ]],
        ])]);

        $result = (new DistributionApiClient(self::URL, $http))->reservationUpsert($this->install(), new Reservation(
            integrationId: '5',
            externalId: 'ABJQXKRZ55',
            revisionId: 'rev-001',
            status: 'NEW',
            checkIn: '2026-08-10',
            checkOut: '2026-08-12',
            currency: 'EUR',
            paymentCollect: 'OTA',
            guestFirstName: 'Ana',
            guestLastName: 'Petrova',
            totalAmount: 260.0,
            rooms: [new ReservationRoom('listing-7', null, 2, 0, [['date' => '2026-08-10', 'price' => 130.0]])],
        ));

        $this->assertTrue($result->ok());
        $this->assertSame('123', $result->bookingId);
        $this->assertSame('synced', $result->status);
        $http->assertSent(fn ($request) => str_contains($request['query'], 'reservationUpsert')
            && $request['variables']['input']['externalId'] === 'ABJQXKRZ55');
    }

    public function test_reservation_upsert_surfaces_user_errors(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response([
            'data' => ['reservationUpsert' => [
                'reservation' => null,
                'userErrors' => [['field' => ['input', 'integrationId'], 'message' => 'Integration not found.']],
            ]],
        ])]);

        $result = (new DistributionApiClient(self::URL, $http))->reservationUpsert($this->install(), new Reservation(
            integrationId: '404', externalId: 'x', revisionId: 'r', status: 'NEW',
            checkIn: '2026-08-10', checkOut: '2026-08-12', currency: 'EUR', paymentCollect: 'OTA',
            guestFirstName: 'A', guestLastName: 'B', totalAmount: 1.0,
            rooms: [new ReservationRoom('l', null, 1, 0, [['date' => '2026-08-10', 'price' => 1.0]])],
        ));

        $this->assertFalse($result->ok());
        $this->assertNotEmpty($result->userErrors);
    }

    public function test_property_content_and_unit_type_rates(): void
    {
        // Illuminate\Http\Client\Factory in this version exposes sequence(), not
        // responseSequence(); build a ResponseSequence and fake the URL with it.
        $http = new Factory;
        $http->fake([self::URL => $http->sequence([
            $http->response(['data' => ['property' => ['id' => '9', 'name' => 'Villa', 'description' => 'Seafront', 'unitTypes' => []]]]),
            $http->response(['data' => ['unitTypeRates' => [['date' => '2026-08-01', 'rate' => 129.0, 'available' => 2]]]]),
        ])]);

        $client = new DistributionApiClient(self::URL, $http);

        $property = $client->propertyContent($this->install(), '9');
        $this->assertSame('Seafront', $property['description']);

        $rates = $client->unitTypeRates($this->install(), '11', '2026-08-01', '2026-08-31');
        // assertEquals, not assertSame: PHP's json_encode() (used internally by
        // the response() fake to build the body) drops the trailing .0 from
        // whole-number floats, so the round trip decodes as int(129).
        $this->assertEquals(129.0, $rates[0]['rate']);
    }
}
