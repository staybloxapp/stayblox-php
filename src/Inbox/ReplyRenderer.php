<?php

declare(strict_types=1);

namespace Stayblox\Inbox;

use Stayblox\Inbox\Dto\OutboundMessage;

/**
 * Degrades an outbound reply to what a channel can carry. v1 emits plain text;
 * the seam exists so quick-replies/buttons can be added per caps later.
 */
class ReplyRenderer
{
    public function toText(OutboundMessage $message): string
    {
        return (string) ($message->body ?? '');
    }
}
