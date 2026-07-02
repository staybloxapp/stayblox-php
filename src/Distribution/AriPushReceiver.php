<?php

declare(strict_types=1);

namespace Stayblox\Distribution;

use Stayblox\Distribution\Dto\AriApplyResult;
use Stayblox\Distribution\Dto\AriPushCommand;

/**
 * Parses a verified ari_push command into a normalized AriPushCommand, hands it
 * to the app's apply handler, and maps the AriApplyResult into the JSON core
 * expects. Run behind VerifyStaybloxSignature; this class assumes the request
 * is authentic.
 */
class AriPushReceiver
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  callable(AriPushCommand): AriApplyResult  $handler
     * @return array{status: string, error: ?string}
     */
    public function handle(array $payload, callable $handler): array
    {
        $command = AriPushCommand::fromPayload($payload);
        $result = $handler($command);

        return $result->toResponse();
    }
}
