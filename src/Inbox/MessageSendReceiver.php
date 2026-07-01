<?php

declare(strict_types=1);

namespace Stayblox\Inbox;

use Stayblox\Inbox\Dto\OutboundMessage;
use Stayblox\Inbox\Dto\SendResult;

/**
 * Parses a verified message_send command into a normalized OutboundMessage, hands
 * it to the app's send handler, and maps the SendResult into the JSON core expects.
 * Run behind VerifyStaybloxSignature; this class assumes the request is authentic.
 */
class MessageSendReceiver
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  callable(OutboundMessage): SendResult  $handler
     * @return array{status: string, provider_message_id: ?string, error: ?string}
     */
    public function handle(array $payload, callable $handler): array
    {
        $message = OutboundMessage::fromPayload($payload);
        $result = $handler($message);

        return $result->toResponse();
    }
}
