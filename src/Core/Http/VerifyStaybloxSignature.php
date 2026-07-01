<?php

declare(strict_types=1);

namespace Stayblox\Core\Http;

use Closure;
use Illuminate\Http\Request;
use Stayblox\Core\Installs\InstallRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies a signed Stayblox core->app command on the raw body and binds the
 * resolved install to the request as `stayblox_install`. Resolution is by the
 * X-Stayblox-Team slug; the signature is HMAC-SHA256 of "{timestamp}.{body}"
 * keyed by that install's webhook_secret, within a freshness window.
 */
class VerifyStaybloxSignature
{
    private const FRESHNESS_SECONDS = 300;

    public function __construct(private readonly InstallRepository $installs = new InstallRepository) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->header('X-Stayblox-Team', '');
        $timestamp = (string) $request->header('X-Stayblox-Timestamp', '');
        $signature = (string) $request->header('X-Stayblox-Signature', '');

        $install = $slug === '' ? null : $this->installs->findByTeamSlug($slug);

        if ($install === null) {
            return $this->reject();
        }

        if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > self::FRESHNESS_SECONDS) {
            return $this->reject();
        }

        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->getContent(), (string) $install->webhook_secret);

        if (! hash_equals($expected, $signature)) {
            return $this->reject();
        }

        $request->attributes->set('stayblox_install', $install);

        return $next($request);
    }

    /**
     * Returns a uniform failure body regardless of which check failed. This is
     * deliberate — callers must not learn which step rejected them, as that
     * would allow team-slug enumeration or signature-window probing.
     */
    private function reject(): Response
    {
        return response()->json(['status' => 'failed', 'error' => 'invalid signature'], 401);
    }
}
