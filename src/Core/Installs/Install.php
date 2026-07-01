<?php

declare(strict_types=1);

namespace Stayblox\Core\Installs;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per Stayblox team that installed the app. Token + secret are encrypted
 * at rest. team_slug is the join key for signed core->app commands.
 *
 * @property string $team_slug
 * @property string $access_token
 * @property string $webhook_secret
 * @property array<int, string> $granted_scopes
 * @property string $status
 */
class Install extends Model
{
    protected $table = 'stayblox_installs';

    protected $guarded = [];

    protected $casts = [
        'access_token' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'granted_scopes' => 'array',
    ];
}
