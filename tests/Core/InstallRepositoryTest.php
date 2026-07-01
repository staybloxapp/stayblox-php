<?php

declare(strict_types=1);

namespace Stayblox\Tests\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Stayblox\Core\Installs\Install;
use Stayblox\Core\Installs\InstallRepository;
use Stayblox\Tests\TestCase;

class InstallRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_then_find_by_slug(): void
    {
        $repo = new InstallRepository;

        $repo->store('acme-stays', 'tok-1', 'whsec-1', ['provide_inbox_channel']);

        $found = $repo->findByTeamSlug('acme-stays');
        $this->assertInstanceOf(Install::class, $found);
        $this->assertSame('tok-1', $found->access_token);
        $this->assertSame('whsec-1', $found->webhook_secret);
        $this->assertSame(['provide_inbox_channel'], $found->granted_scopes);
        $this->assertSame('active', $found->status);
    }

    public function test_store_upserts_on_team_slug(): void
    {
        $repo = new InstallRepository;
        $repo->store('acme-stays', 'tok-1', 'whsec-1', ['provide_inbox_channel']);
        $repo->store('acme-stays', 'tok-2', 'whsec-2', ['provide_inbox_channel', 'read_bookings']);

        $this->assertSame(1, Install::query()->where('team_slug', 'acme-stays')->count());
        $this->assertSame('tok-2', $repo->findByTeamSlug('acme-stays')->access_token);
    }

    public function test_tokens_are_encrypted_at_rest(): void
    {
        (new InstallRepository)->store('acme-stays', 'tok-secret', 'whsec', ['x']);

        $raw = DB::table('stayblox_installs')->where('team_slug', 'acme-stays')->value('access_token');
        $this->assertNotSame('tok-secret', $raw); // stored ciphertext, not plaintext
    }

    public function test_unknown_slug_returns_null(): void
    {
        $this->assertNull((new InstallRepository)->findByTeamSlug('nope'));
    }
}
