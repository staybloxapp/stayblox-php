<?php

declare(strict_types=1);

namespace Stayblox\Core\OAuth;

use Illuminate\Http\Client\Factory;
use RuntimeException;

/**
 * Speaks Stayblox's OAuth 2.0 authorization-code flow: builds the consent URL and
 * exchanges the returned code for a per-install bearer token + webhook secret.
 */
final class OAuthClient
{
    private readonly Factory $http;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $ownerBaseUrl,
        ?Factory $http = null,
    ) {
        $this->http = $http ?? new Factory;
    }

    /** @param list<string> $scopes */
    public function authorizeUrl(string $redirectUri, array $scopes, string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => $state,
        ]);

        return rtrim($this->ownerBaseUrl, '/').'/oauth/authorize?'.$query;
    }

    public function exchange(string $code, string $redirectUri): TokenResult
    {
        $response = $this->http->asForm()->post(rtrim($this->ownerBaseUrl, '/').'/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('OAuth token exchange failed: '.$response->body());
        }

        $data = $response->json();

        return new TokenResult(
            accessToken: (string) ($data['access_token'] ?? ''),
            scopes: array_values(array_filter(explode(' ', (string) ($data['scope'] ?? '')))),
            webhookSecret: (string) ($data['webhook_secret'] ?? ''),
        );
    }
}
