<?php

declare(strict_types=1);

namespace Stayblox\Inbox\Dto;

/**
 * A normalized inbound guest message to inject into the Stayblox inbox.
 */
final class InboundMessage
{
    /**
     * @param  list<array<string, mixed>>  $attachments
     * @param  array<string, mixed>  $contactHints
     */
    public function __construct(
        public readonly string $channel,
        public readonly string $externalThreadId,
        public readonly string $senderIdentifier,
        public readonly ?string $body = null,
        public readonly string $bodyFormat = 'text',
        public readonly array $attachments = [],
        public readonly ?string $externalMessageId = null,
        public readonly ?string $sentAt = null,
        public readonly array $contactHints = [],
    ) {}

    /** @return array<string, mixed> */
    public function toInput(): array
    {
        return array_filter([
            'channel' => $this->channel,
            'externalThreadId' => $this->externalThreadId,
            'senderIdentifier' => $this->senderIdentifier,
            'body' => $this->body,
            'bodyFormat' => $this->bodyFormat,
            'attachments' => $this->attachments === [] ? null : $this->attachments,
            'externalMessageId' => $this->externalMessageId,
            'sentAt' => $this->sentAt,
            'contactHints' => $this->contactHints === [] ? null : $this->contactHints,
        ], fn ($v) => $v !== null);
    }
}
