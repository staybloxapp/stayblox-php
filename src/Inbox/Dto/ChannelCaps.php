<?php

declare(strict_types=1);

namespace Stayblox\Inbox\Dto;

/**
 * The capabilities a channel declares in its manifest. Producers branch on these,
 * never on channel identity.
 */
final class ChannelCaps
{
    public function __construct(
        public readonly bool $richText,
        public readonly bool $quickReplies,
        public readonly ?int $outboundWindowHours,
        public readonly bool $templateRequiredOutsideWindow,
    ) {}
}
