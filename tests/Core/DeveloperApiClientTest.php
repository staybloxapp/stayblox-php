<?php

declare(strict_types=1);

namespace Stayblox\Tests\Core;

use Illuminate\Http\Client\Factory;
use Stayblox\Core\Api\ApiException;
use Stayblox\Core\Api\DeveloperApiClient;
use Stayblox\Core\Installs\Install;
use Stayblox\Tests\TestCase;

class DeveloperApiClientTest extends TestCase
{
    private const URL = 'https://admin.stayblox.com/developer/api/2026-01/graphql';

    private function install(): Install
    {
        return new Install(['team_slug' => 'acme-stays', 'access_token' => 'tok-1', 'webhook_secret' => 'x']);
    }

    public function test_current_app_sends_bearer_and_parses_team_slug(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response([
            'data' => ['currentApp' => ['id' => '9', 'name' => 'FB', 'type' => 'remote', 'teamSlug' => 'acme-stays']],
        ])]);

        $app = (new DeveloperApiClient(self::URL, $http))->currentApp($this->install());

        $this->assertSame('acme-stays', $app['teamSlug']);
        $http->assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer tok-1')
            && str_contains($request['query'], 'currentApp'));
    }

    public function test_top_level_graphql_errors_throw(): void
    {
        $http = new Factory;
        $http->fake([self::URL => $http->response(['errors' => [['message' => 'boom']]])]);

        $this->expectException(ApiException::class);
        (new DeveloperApiClient(self::URL, $http))->query($this->install(), 'query { x }');
    }
}
