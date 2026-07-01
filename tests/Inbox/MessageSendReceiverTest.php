<?php

declare(strict_types=1);

namespace Stayblox\Tests\Inbox;

use Stayblox\Inbox\Dto\OutboundMessage;
use Stayblox\Inbox\Dto\SendResult;
use Stayblox\Inbox\MessageSendReceiver;
use Stayblox\Tests\TestCase;

class MessageSendReceiverTest extends TestCase
{
    private function payload(): array
    {
        return [
            'message_id' => 123,
            'conversation_id' => 45,
            'channel' => 'messenger',
            'recipient' => ['external_thread_id' => 'psid-9', 'identifier' => 'psid-9'],
            'body' => 'See you then!',
            'body_format' => 'text',
            'attachments' => [],
            'settings' => ['foo' => 'bar'],
            'api_base_url' => 'https://admin.stayblox.com/developer/api/2026-01/graphql',
        ];
    }

    public function test_handler_receives_normalized_message_and_sent_maps_to_response(): void
    {
        $seen = null;
        $response = (new MessageSendReceiver)->handle($this->payload(), function (OutboundMessage $m) use (&$seen) {
            $seen = $m;

            return SendResult::sent('pmid-1');
        });

        $this->assertSame('psid-9', $seen->externalThreadId);
        $this->assertSame('messenger', $seen->channel);
        $this->assertSame('See you then!', $seen->body);
        $this->assertSame(['foo' => 'bar'], $seen->settings);

        $this->assertSame(['status' => 'sent', 'provider_message_id' => 'pmid-1', 'error' => null], $response);
    }

    public function test_failed_result_maps_to_response(): void
    {
        $response = (new MessageSendReceiver)->handle($this->payload(), fn () => SendResult::failed('outside messaging window'));

        $this->assertSame('failed', $response['status']);
        $this->assertSame('outside messaging window', $response['error']);
        $this->assertNull($response['provider_message_id']);
    }
}
