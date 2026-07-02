<?php

declare(strict_types=1);

namespace Stayblox\Tests\Distribution;

use Stayblox\Distribution\AriPushReceiver;
use Stayblox\Distribution\Dto\AriApplyResult;
use Stayblox\Distribution\Dto\AriPushCommand;
use Stayblox\Tests\TestCase;

class AriPushReceiverTest extends TestCase
{
    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
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
        ];
    }

    public function test_parses_payload_and_maps_applied_result(): void
    {
        $seen = null;

        $response = (new AriPushReceiver)->handle($this->payload(), function (AriPushCommand $command) use (&$seen): AriApplyResult {
            $seen = $command;

            return AriApplyResult::applied();
        });

        $this->assertSame(['status' => 'applied', 'error' => null], $response);
        $this->assertInstanceOf(AriPushCommand::class, $seen);
        $this->assertSame('airbnb-host-9', $seen->externalPropertyId);
        $this->assertSame('listing-7', $seen->listings[0]->externalRoomTypeId);
    }

    public function test_maps_failed_result(): void
    {
        $response = (new AriPushReceiver)->handle(
            $this->payload(),
            static fn (AriPushCommand $command): AriApplyResult => AriApplyResult::failed('listing archived'),
        );

        $this->assertSame(['status' => 'failed', 'error' => 'listing archived'], $response);
    }
}
