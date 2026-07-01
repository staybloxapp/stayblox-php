<?php

declare(strict_types=1);

namespace Stayblox\Core;

use Illuminate\Support\ServiceProvider;
use Stayblox\Core\Http\VerifyStaybloxSignature;

/**
 * Wires the Stayblox SDK into a host Laravel app: publishable migration and the
 * signed-request middleware alias. Config is supplied by the host app and passed
 * explicitly into the SDK clients (see OAuthClient / DeveloperApiClient), so the
 * SDK stays usable without Laravel config in tests.
 */
class StaybloxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->app['router']->aliasMiddleware('stayblox.signed', VerifyStaybloxSignature::class);
    }
}
