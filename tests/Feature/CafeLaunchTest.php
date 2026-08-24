<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CafeLaunchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_cannot_open_cafe(): void
    {
        $this->get(route('cafe.launch'))->assertRedirect();
    }

    public function test_employee_without_code_is_sent_back_to_dashboard(): void
    {
        config([
            'services.cafe.base_url' => 'https://cafe.khanmusa.com',
            'services.cafe.sso_secret' => str_repeat('c', 32),
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_code' => null,
        ]);

        $this->actingAs($user)
            ->get(route('cafe.launch'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_employee_with_code_is_redirected_to_cafe_sso(): void
    {
        config([
            'services.cafe.base_url' => 'https://cafe.khanmusa.com',
            'services.cafe.sso_secret' => str_repeat('d', 32),
            'services.cafe.sso_ttl' => 120,
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'LSAF-001',
        ]);

        $response = $this->actingAs($user)->get(route('cafe.launch'));

        $response->assertRedirect();
        $this->assertStringStartsWith(
            'https://cafe.khanmusa.com/sso?token=',
            $response->headers->get('Location')
        );
    }

    public function test_without_sso_secret_falls_back_to_cafe_login(): void
    {
        config([
            'services.cafe.base_url' => 'https://cafe.khanmusa.com',
            'services.cafe.sso_secret' => null,
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'LSAF-001',
        ]);

        $this->actingAs($user)
            ->get(route('cafe.launch'))
            ->assertRedirect('https://cafe.khanmusa.com/login');
    }

    public function test_cafe_appears_on_dashboard_and_in_employee_panel(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cafe')
            ->assertSee(route('cafe.launch'), false);
    }
}
