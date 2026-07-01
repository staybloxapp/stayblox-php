<?php

declare(strict_types=1);

namespace Stayblox\Core\Installs;

class InstallRepository
{
    /** @param list<string> $scopes */
    public function store(string $teamSlug, string $accessToken, string $webhookSecret, array $scopes, string $status = 'active'): Install
    {
        return Install::query()->updateOrCreate(
            ['team_slug' => $teamSlug],
            [
                'access_token' => $accessToken,
                'webhook_secret' => $webhookSecret,
                'granted_scopes' => array_values($scopes),
                'status' => $status,
            ],
        );
    }

    public function findByTeamSlug(string $teamSlug): ?Install
    {
        return Install::query()->where('team_slug', $teamSlug)->first();
    }
}
