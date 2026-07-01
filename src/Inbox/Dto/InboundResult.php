<?php

declare(strict_types=1);

namespace Stayblox\Inbox\Dto;

final class InboundResult
{
    /** @param list<array<string, mixed>> $userErrors */
    public function __construct(
        public readonly ?string $conversationId,
        public readonly ?string $messageId,
        public readonly array $userErrors,
    ) {}

    public function ok(): bool
    {
        return $this->userErrors === [];
    }
}
