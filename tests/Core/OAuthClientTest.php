<?php

declare(strict_types=1);

namespace Stayblox\Tests\Core;

use Illuminate\Http\Client\Factory;
use Stayblox\Core\OAuth\OAuthClient;
use Stayblox\Tests\TestCase;

class OAuthClientTest extends TestCase
{
    private function client(?Factory $http = null): OAuthClient
    {
        return new OAuthClient('client-123', 'secret-xyz', 'https://admin.stayblox.com', $http);
    }

    public function test_authorize_url_is_built_from_the_parts(): void
    {
        $url = $this->client()->authorizeUrl('https://app.test/oauth/callback', ['provide_inbox_channel'], 'state-1');

        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('https://admin.stayblox.com/oauth/authorize', strtok($url, '?'));
        $this->assertSame('code', $q['response_type']);
        $this->assertSame('client-123', $q['client_id']);
        $this->assertSame('https://app.test/oauth/callback', $q['redirect_uri']);
        $this->assertSame('provide_inbox_channel', $q['scope']);
        $this->assertSame('state-1', $q['state']);
    }

    public function test_exchange_posts_the_code_and_parses_the_token(): void
    {
        $http = new Factory;
        $http->fake(['admin.stayblox.com/oauth/token' => $http->response([
            'access_token' => 'tok-abc',
            'token_type' => 'Bearer',
            'scope' => 'provide_inbox_channel read_bookings',
            'webhook_secret' => 'whsec-1',
        ])]);

        $result = $this->client($http)->exchange('the-code', 'https://app.test/oauth/callback');

        $this->assertSame('tok-abc', $result->accessToken);
        $this->assertSame('whsec-1', $result->webhookSecret);
        $this->assertSame(['provide_inbox_channel', 'read_bookings'], $result->scopes);

        $http->assertSent(fn ($request) => $request->url() === 'https://admin.stayblox.com/oauth/token'
            && $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'the-code'
            && $request['client_id'] === 'client-123'
            && $request['client_secret'] === 'secret-xyz'
            && $request['redirect_uri'] === 'https://app.test/oauth/callback');
    }
}
