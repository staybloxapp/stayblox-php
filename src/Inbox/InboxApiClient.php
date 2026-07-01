<?php

declare(strict_types=1);

namespace Stayblox\Inbox;

use Stayblox\Core\Api\DeveloperApiClient;
use Stayblox\Core\Installs\Install;
use Stayblox\Inbox\Dto\InboundMessage;
use Stayblox\Inbox\Dto\InboundResult;

/**
 * The messaging app->core half: inject inbound guest messages and report
 * delivery/read receipts for messages this app sent.
 */
class InboxApiClient extends DeveloperApiClient
{
    private const INBOUND = 'mutation($input: InboundMessageInput!) {
        inboundMessageCreate(input: $input) { conversationId messageId userErrors { field message } }
    }';

    private const STATUS = 'mutation($input: MessageStatusInput!) {
        messageStatusUpdate(input: $input) { ok userErrors { field message } }
    }';

    public function inboundMessageCreate(Install $install, InboundMessage $message): InboundResult
    {
        $data = $this->query($install, self::INBOUND, ['input' => $message->toInput()]);
        $payload = $data['inboundMessageCreate'] ?? [];

        return new InboundResult(
            conversationId: $payload['conversationId'] ?? null,
            messageId: $payload['messageId'] ?? null,
            userErrors: $payload['userErrors'] ?? [],
        );
    }

    public function messageStatusUpdate(Install $install, string $externalMessageId, string $status): bool
    {
        $data = $this->query($install, self::STATUS, ['input' => [
            'externalMessageId' => $externalMessageId,
            'status' => $status,
        ]]);

        return (bool) ($data['messageStatusUpdate']['ok'] ?? false);
    }
}
