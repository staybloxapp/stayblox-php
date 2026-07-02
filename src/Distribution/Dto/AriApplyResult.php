<?php

declare(strict_types=1);

namespace Stayblox\Distribution\Dto;

/** The app's outcome for an ari_push command, mapped to the JSON core expects. */
final class AriApplyResult
{
    private function __construct(
        public readonly string $status,
        public readonly ?string $error,
    ) {}

    public static function applied(): self
    {
        return new self('applied', null);
    }

    public static function failed(string $error): self
    {
        return new self('failed', $error);
    }

    /** @return array{status: string, error: ?string} */
    public function toResponse(): array
    {
        return ['status' => $this->status, 'error' => $this->error];
    }
}
