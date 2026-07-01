<?php

declare(strict_types=1);

namespace Stayblox\Core\OAuth;

/**
 * The result of an OAuth code->token exchange. webhookSecret is used later to
 * verify signed core->app commands (message_send).
 */
final class TokenResult
{
    /** @param list<string> $scopes */
    public function __construct(
        public readonly string $accessToken,
        public readonly array $scopes,
        public readonly string $webhookSecret,
    ) {}
}
