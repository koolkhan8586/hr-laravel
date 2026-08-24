<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\CafeSsoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CafeSsoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_launch_url_is_null_without_secret(): void
    {
        config([
            'services.cafe.base_url' => 'https://cafe.khanmusa.com',
            'services.cafe.sso_secret' => null,
        ]);

        $user = User::factory()->create(['employee_code' => 'LSAF-001']);
        $sso = new CafeSsoService;

        $this->assertFalse($sso->configured());
        $this->assertNull($sso->launchUrl($user));
    }

    public function test_launch_url_is_null_without_employee_code(): void
    {
        config([
            'services.cafe.base_url' => 'https://cafe.khanmusa.com',
            'services.cafe.sso_secret' => str_repeat('a', 32),
            'services.cafe.sso_ttl' => 120,
        ]);

        $user = User::factory()->create(['employee_code' => null]);
        $sso = new CafeSsoService;

        $this->assertTrue($sso->configured());
        $this->assertNull($sso->launchUrl($user));
    }

    public function test_launch_url_is_signed_and_uses_uppercase_code(): void
    {
        $secret = str_repeat('b', 32);

        config([
            'services.cafe.base_url' => 'https://cafe.khanmusa.com/',
            'services.cafe.sso_secret' => $secret,
            'services.cafe.sso_ttl' => 120,
        ]);

        $user = User::factory()->create([
            'employee_code' => 'lsaf-014',
            'name' => 'Ammad Khan',
        ]);

        $url = (new CafeSsoService)->launchUrl($user);

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://cafe.khanmusa.com/sso?token=', $url);

        $token = urldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertMatchesRegularExpression('/^token=[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $token);

        $raw = substr($token, strlen('token='));
        [$body, $sig] = explode('.', $raw, 2);

        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', $body, $secret, true)), '+/', '-_'), '=');
        $this->assertSame($expected, $sig);

        $payload = json_decode(base64_decode(strtr($body, '-_', '+/')), true);
        $this->assertSame('LSAF-014', $payload['code']);
        $this->assertSame('Ammad Khan', $payload['name']);
        $this->assertGreaterThan(time(), $payload['exp']);
    }
}
