<?php

declare(strict_types=1);

namespace Stayblox\Tests\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Stayblox\Core\Http\VerifyStaybloxSignature;
use Stayblox\Core\Installs\Install;
use Stayblox\Core\Installs\InstallRepository;
use Stayblox\Tests\TestCase;

class VerifyStaybloxSignatureTest extends TestCase
{
    use RefreshDatabase;

    private function request(string $body, array $headers): Request
    {
        $request = Request::create('/message-send', 'POST', [], [], [], [], $body);
        foreach ($headers as $k => $v) {
            $request->headers->set($k, $v);
        }

        return $request;
    }

    private function sign(string $secret, string $ts, string $body): string
    {
        return 'sha256='.hash_hmac('sha256', $ts.'.'.$body, $secret);
    }

    public function test_valid_signature_passes_and_binds_install(): void
    {
        (new InstallRepository)->store('acme-stays', 'tok', 'whsec', ['provide_inbox_channel']);
        $body = '{"message_id":1}';
        $ts = (string) time();

        $request = $this->request($body, [
            'X-Stayblox-Team' => 'acme-stays',
            'X-Stayblox-Timestamp' => $ts,
            'X-Stayblox-Signature' => $this->sign('whsec', $ts, $body),
        ]);

        $passed = false;
        $response = (new VerifyStaybloxSignature)->handle($request, function (Request $r) use (&$passed) {
            $passed = true;
            $this->assertInstanceOf(Install::class, $r->attributes->get('stayblox_install'));

            return response('ok');
        });

        $this->assertTrue($passed);
        $this->assertSame('ok', $response->getContent());
    }

    public function test_bad_signature_is_rejected(): void
    {
        (new InstallRepository)->store('acme-stays', 'tok', 'whsec', []);
        $ts = (string) time();
        $request = $this->request('{"x":1}', [
            'X-Stayblox-Team' => 'acme-stays',
            'X-Stayblox-Timestamp' => $ts,
            'X-Stayblox-Signature' => 'sha256=deadbeef',
        ]);

        $response = (new VerifyStaybloxSignature)->handle($request, fn () => response('ok'));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_stale_timestamp_is_rejected(): void
    {
        (new InstallRepository)->store('acme-stays', 'tok', 'whsec', []);
        $body = '{"x":1}';
        $ts = (string) (time() - 4000);
        $request = $this->request($body, [
            'X-Stayblox-Team' => 'acme-stays',
            'X-Stayblox-Timestamp' => $ts,
            'X-Stayblox-Signature' => $this->sign('whsec', $ts, $body),
        ]);

        $response = (new VerifyStaybloxSignature)->handle($request, fn () => response('ok'));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_unknown_team_is_rejected(): void
    {
        $body = '{"x":1}';
        $ts = (string) time();
        $request = $this->request($body, [
            'X-Stayblox-Team' => 'ghost',
            'X-Stayblox-Timestamp' => $ts,
            'X-Stayblox-Signature' => $this->sign('whatever', $ts, $body),
        ]);

        $response = (new VerifyStaybloxSignature)->handle($request, fn () => response('ok'));
        $this->assertSame(401, $response->getStatusCode());
    }
}
