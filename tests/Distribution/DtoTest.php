<?php

declare(strict_types=1);

namespace Stayblox\Tests\Distribution;

use Stayblox\Distribution\Dto\AriApplyResult;
use Stayblox\Distribution\Dto\AriPushCommand;
use Stayblox\Distribution\Dto\Reservation;
use Stayblox\Distribution\Dto\ReservationRoom;
use Stayblox\Tests\TestCase;

class DtoTest extends TestCase
{
    public function test_ari_push_command_parses_wire_payload(): void
    {
        $command = AriPushCommand::fromPayload([
            'external_property_id' => 'airbnb-host-9',
            'listings' => [[
                'external_room_type_id' => 'listing-7',
                'external_rate_plan_id' => null,
                'dates' => [[
                    'date' => '2026-08-01', 'rate' => 129.0, 'availability' => 2,
                    'min_stay' => 2, 'max_stay' => null, 'closed_to_arrival' => false,
                    'closed_to_departure' => false, 'stop_sell' => false,
                ]],
            ]],
            'api_base_url' => 'https://admin.stayblox.com/developer/api/2026-01/graphql',
        ]);

        $this->assertSame('airbnb-host-9', $command->externalPropertyId);
        $this->assertCount(1, $command->listings);
        $this->assertSame('listing-7', $command->listings[0]->externalRoomTypeId);
        $date = $command->listings[0]->dates[0];
        $this->assertSame('2026-08-01', $date->date);
        $this->assertSame(129.0, $date->rate);
        $this->assertSame(2, $date->availability);
        $this->assertSame(2, $date->minStay);
        $this->assertNull($date->maxStay);
        $this->assertFalse($date->stopSell);
    }

    public function test_ari_apply_result_responses(): void
    {
        $this->assertSame(['status' => 'applied', 'error' => null], AriApplyResult::applied()->toResponse());
        $this->assertSame(['status' => 'failed', 'error' => 'listing archived'], AriApplyResult::failed('listing archived')->toResponse());
    }

    public function test_reservation_to_input_matches_graphql_shape(): void
    {
        $reservation = new Reservation(
            integrationId: '5',
            externalId: 'ABJQXKRZ55',
            revisionId: 'rev-002',
            status: 'MODIFIED',
            checkIn: '2026-08-10',
            checkOut: '2026-08-12',
            currency: 'EUR',
            paymentCollect: 'OTA',
            guestFirstName: 'Ana',
            guestLastName: 'Petrova',
            totalAmount: 260.0,
            rooms: [new ReservationRoom(
                externalRoomTypeId: 'listing-7',
                externalRatePlanId: null,
                adults: 2,
                children: 0,
                nights: [['date' => '2026-08-10', 'price' => 130.0], ['date' => '2026-08-11', 'price' => 130.0]],
            )],
            guestEmail: 'ana@example.com',
        );

        $input = $reservation->toInput();

        $this->assertSame('5', $input['integrationId']);
        $this->assertSame('MODIFIED', $input['status']);
        $this->assertSame('OTA', $input['paymentCollect']);
        $this->assertSame(['firstName' => 'Ana', 'lastName' => 'Petrova', 'email' => 'ana@example.com'], $input['guest']);
        $this->assertSame('listing-7', $input['rooms'][0]['externalRoomTypeId']);
        $this->assertArrayNotHasKey('externalRatePlanId', $input['rooms'][0]);
        $this->assertCount(2, $input['rooms'][0]['nights']);
        $this->assertArrayNotHasKey('externalPaymentId', $input);
    }
}
