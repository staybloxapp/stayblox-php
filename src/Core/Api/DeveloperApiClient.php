<?php

declare(strict_types=1);

namespace Stayblox\Core\Api;

use Illuminate\Http\Client\Factory;
use Stayblox\Core\Installs\Install;
use Throwable;

/**
 * Per-install authenticated GraphQL client for the Stayblox Developer API.
 * Owns the bearer header, transport, and top-level error surfacing. Mutation
 * userErrors are handled by the typed methods that build on query().
 */
class DeveloperApiClient
{
    protected readonly Factory $http;

    public function __construct(
        protected readonly string $graphqlUrl,
        ?Factory $http = null,
    ) {
        $this->http = $http ?? new Factory;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed> the decoded GraphQL `data`
     */
    public function query(Install $install, string $query, array $variables = []): array
    {
        try {
            $response = $this->http
                ->withToken($install->access_token)
                ->acceptJson()
                ->timeout(20)
                ->post($this->graphqlUrl, [
                    'query' => $query,
                    // Empty variables must serialise as a JSON object {}, not []; a
                    // non-empty array stays an array so callers/tests can index it.
                    'variables' => $variables === [] ? new \stdClass : $variables,
                ]);
        } catch (Throwable $e) {
            throw new ApiException("Developer API request failed: {$e->getMessage()}", previous: $e);
        }

        if (! $response->successful()) {
            throw new ApiException('Developer API HTTP '.$response->status().': '.$response->body());
        }

        $body = $response->json() ?? [];

        if (! empty($body['errors'])) {
            throw new ApiException('Developer API error: '.json_encode($body['errors']));
        }

        return $body['data'] ?? [];
    }

    /**
     * @return array{id: string, name: string, type: string, teamSlug: string}
     */
    public function currentApp(Install $install): array
    {
        $data = $this->query($install, 'query { currentApp { id name type teamSlug } }');

        /** @var array{id: string, name: string, type: string, teamSlug: string} $app */
        $app = $data['currentApp'] ?? [];

        return $app;
    }
}
