<?php

declare(strict_types=1);

namespace Stayblox\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Stayblox\Core\Installs\InstallRepository;
use Stayblox\Inbox\Dto\OutboundMessage;
use Stayblox\Inbox\Dto\SendResult;
use Stayblox\Inbox\MessageSendReceiver;
use Stayblox\Tests\TestCase;

class SignedRouteCompositionTest extends TestCase
{
    use RefreshDatabase;

    protected function defineRoutes($router): void
    {
        Route::post('/message-send', fn (Request $r) => response()->json(
            (new MessageSendReceiver)->handle($r->json()->all(), fn (OutboundMessage $m) => SendResult::sent('pmid-1'))
        ))->middleware('stayblox.signed');
    }

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
            'settings' => [],
            'api_base_url' => 'https://admin.stayblox.com/developer/api/2026-01/graphql',
        ];
    }

    private function sign(string $secret, string $ts, string $body): string
    {
        return 'sha256='.hash_hmac('sha256', $ts.'.'.$body, $secret);
    }

    public function test_valid_signed_request_returns_sent_response(): void
    {
        (new InstallRepository)->store('acme-stays', 'tok', 'whsec', ['provide_inbox_channel']);

        $body = json_encode($this->payload());
        $ts = (string) time();
        $signature = $this->sign('whsec', $ts, $body);

        $response = $this->call('POST', '/message-send', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_STAYBLOX_TEAM' => 'acme-stays',
            'HTTP_X_STAYBLOX_TIMESTAMP' => $ts,
            'HTTP_X_STAYBLOX_SIGNATURE' => $signature,
        ], $body);

        $response->assertStatus(200)->assertJson([
            'status' => 'sent',
            'provider_message_id' => 'pmid-1',
            'error' => null,
        ]);
    }

    public function test_wrong_signature_returns_401(): void
    {
        (new InstallRepository)->store('acme-stays', 'tok', 'whsec', ['provide_inbox_channel']);

        $body = json_encode($this->payload());
        $ts = (string) time();

        $response = $this->call('POST', '/message-send', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_STAYBLOX_TEAM' => 'acme-stays',
            'HTTP_X_STAYBLOX_TIMESTAMP' => $ts,
            'HTTP_X_STAYBLOX_SIGNATURE' => 'sha256=deadbeef',
        ], $body);

        $response->assertStatus(401);
    }
}
