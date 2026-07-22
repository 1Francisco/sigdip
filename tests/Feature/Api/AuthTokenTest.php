<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);
    }

    public function test_logout_revoca_token_actual()
    {
        $user = User::factory()->create();
        $user->assignRole('Administrador');

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/logout');

        $response->assertStatus(200);
        $this->assertCount(0, $user->tokens()->where('id', $user->currentAccessToken()?->id ?? 0)->get());
    }

    public function test_token_expirado_o_invalido_recibe_401()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer token-invalido',
        ])->getJson('/api/user');

        $response->assertStatus(401);
    }
}
