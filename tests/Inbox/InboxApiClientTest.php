<?php

declare(strict_types=1);

namespace Stayblox\Tests\Inbox;

use Illuminate\Http\Client\Factory;
use Stayblox\Core\Installs\Install;
use Stayblox\Inbox\Dto\InboundMessage;
use Stayblox\Inbox\InboxApiClient;
use Stayblox\Tests\TestCase;

class InboxApiClientTest extends TestCase
{
    private const URL = 'https://admin.stayblox.com/developer/api/2026-01/graphql';

    private function install(): Install
    {
        return new Install(['team_slug' => 'acme', 'access_token' => 'tok', 'webhook_secret' => 'x']);
    }

    public function test_inbound_message_create_sends_variables_and_parses_result(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response([
            'data' => ['inboundMessageCreate' => ['conversationId' => '12', 'messageId' => '34', 'userErrors' => []]],
        ])]);

        $client = new InboxApiClient(self::URL, $http);
        $result = $client->inboundMessageCreate($this->install(), new InboundMessage(
            channel: 'messenger',
            externalThreadId: 'psid-1',
            senderIdentifier: 'psid-1',
            body: 'Is the loft free in July?',
            externalMessageId: 'mid-1',
            contactHints: ['firstName' => 'Dana'],
        ));

        $this->assertTrue($result->ok());
        $this->assertSame('12', $result->conversationId);
        $this->assertSame('34', $result->messageId);

        $http->assertSent(function ($request) {
            $input = $request['variables']['input'];

            return str_contains($request['query'], 'inboundMessageCreate')
                && $input['channel'] === 'messenger'
                && $input['externalThreadId'] === 'psid-1'
                && $input['externalMessageId'] === 'mid-1'
                && $input['contactHints'] === ['firstName' => 'Dana'];
        });
    }

    public function test_inbound_message_create_surfaces_user_errors(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response([
            'data' => ['inboundMessageCreate' => ['conversationId' => null, 'messageId' => null,
                'userErrors' => [['field' => ['input', 'channel'], 'message' => 'not provided']]]],
        ])]);

        $result = (new InboxApiClient(self::URL, $http))->inboundMessageCreate($this->install(), new InboundMessage(
            channel: 'whatsapp', externalThreadId: 't', senderIdentifier: 's',
        ));

        $this->assertFalse($result->ok());
        $this->assertNull($result->conversationId);
    }

    public function test_message_status_update_returns_ok(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response([
            'data' => ['messageStatusUpdate' => ['ok' => true, 'userErrors' => []]],
        ])]);

        $ok = (new InboxApiClient(self::URL, $http))->messageStatusUpdate($this->install(), 'mid-1', 'delivered');

        $this->assertTrue($ok);
        $http->assertSent(fn ($request) => $request['variables']['input']['status'] === 'delivered'
            && $request['variables']['input']['externalMessageId'] === 'mid-1');
    }
}
