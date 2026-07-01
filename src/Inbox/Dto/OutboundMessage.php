<?php

declare(strict_types=1);

namespace Stayblox\Inbox\Dto;

/**
 * A normalized outbound reply core asked the app to deliver via its provider.
 */
final class OutboundMessage
{
    /**
     * @param  list<array<string, mixed>>  $attachments
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public readonly int|string|null $messageId,
        public readonly int|string|null $conversationId,
        public readonly string $channel,
        public readonly string $externalThreadId,
        public readonly string $identifier,
        public readonly ?string $body,
        public readonly string $bodyFormat,
        public readonly array $attachments,
        public readonly array $settings,
        public readonly ?string $apiBaseUrl,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromPayload(array $payload): self
    {
        $recipient = (array) ($payload['recipient'] ?? []);

        return new self(
            messageId: $payload['message_id'] ?? null,
            conversationId: $payload['conversation_id'] ?? null,
            channel: (string) ($payload['channel'] ?? ''),
            externalThreadId: (string) ($recipient['external_thread_id'] ?? ''),
            identifier: (string) ($recipient['identifier'] ?? ($recipient['external_thread_id'] ?? '')),
            body: $payload['body'] ?? null,
            bodyFormat: (string) ($payload['body_format'] ?? 'text'),
            attachments: (array) ($payload['attachments'] ?? []),
            settings: (array) ($payload['settings'] ?? []),
            apiBaseUrl: $payload['api_base_url'] ?? null,
        );
    }
}
