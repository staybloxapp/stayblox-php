<?php

declare(strict_types=1);

namespace Stayblox\Inbox\Dto;

final class SendResult
{
    private function __construct(
        public readonly string $status,
        public readonly ?string $providerMessageId,
        public readonly ?string $error,
    ) {}

    public static function sent(string $providerMessageId): self
    {
        return new self('sent', $providerMessageId, null);
    }

    public static function failed(string $error): self
    {
        return new self('failed', null, $error);
    }

    /** @return array{status: string, provider_message_id: ?string, error: ?string} */
    public function toResponse(): array
    {
        return [
            'status' => $this->status,
            'provider_message_id' => $this->providerMessageId,
            'error' => $this->error,
        ];
    }
}
