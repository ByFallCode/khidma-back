<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_spring_compatible_jwt(): void
    {
        User::create([
            'username' => '770000000',
            'password' => 'secret',
            'account_type' => 'ADMIN',
            'statut' => true,
            'prenom' => 'Admin',
            'nom' => 'Test',
            'telephone' => '770000000',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => '770000000',
            'password' => 'secret',
        ])->assertOk()->assertJsonStructure(['token']);

        $parts = explode('.', $response->json('token'));
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        $this->assertSame('770000000', $payload['sub']);
        $this->assertSame(['ROLE_ADMIN'], $payload['roles']);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function test_protected_routes_require_a_valid_bearer_token(): void
    {
        $this->getJson('/api/v1/utilisateurs')->assertUnauthorized();
    }

    public function test_authenticated_user_can_read_the_spring_page_format(): void
    {
        $user = User::create([
            'username' => '770000001',
            'password' => 'secret',
            'account_type' => 'KHIDMA_AGENT',
            'statut' => true,
            'prenom' => 'Agent',
            'nom' => 'Test',
            'telephone' => '770000001',
        ]);
        $token = app(JwtService::class)->issue($user);

        $this->withToken($token)->getJson('/api/v1/utilisateurs?page=0&size=20')
            ->assertOk()
            ->assertJsonPath('content.0.accountType', 'KHIDMA_AGENT')
            ->assertJsonPath('number', 0)
            ->assertJsonPath('totalElements', 1);
    }

    public function test_authenticated_user_can_update_their_personal_information(): void
    {
        $user = User::create([
            'username' => '770000002',
            'password' => 'secret',
            'account_type' => 'KHIDMA_AGENT',
            'statut' => true,
            'prenom' => 'Ancien',
            'nom' => 'Nom',
            'telephone' => '770000002',
        ]);
        $token = app(JwtService::class)->issue($user);

        $response = $this->withToken($token)->putJson('/api/v1/utilisateurs/me', [
            'prenom' => 'Nouveau',
            'nom' => 'Profil',
            'telephone' => '770000009',
            'whatsapp' => '780000009',
        ])->assertOk()
            ->assertJsonPath('user.prenom', 'Nouveau')
            ->assertJsonPath('user.nom', 'Profil')
            ->assertJsonPath('user.username', '770000009')
            ->assertJsonPath('user.telephone', '770000009')
            ->assertJsonPath('user.whatsapp', '780000009')
            ->assertJsonStructure(['token']);

        $this->withToken($response->json('token'))->getJson('/api/v1/utilisateurs/info')
            ->assertOk()
            ->assertJsonPath('username', '770000009');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => '770000009',
            'telephone' => '770000009',
        ]);
    }

    public function test_user_cannot_use_another_accounts_phone_number(): void
    {
        User::create([
            'username' => '770000010',
            'password' => 'secret',
            'account_type' => 'ADMIN',
            'statut' => true,
            'prenom' => 'Autre',
            'nom' => 'Compte',
            'telephone' => '770000010',
        ]);
        $user = User::create([
            'username' => '770000011',
            'password' => 'secret',
            'account_type' => 'KHIDMA_AGENT',
            'statut' => true,
            'prenom' => 'Agent',
            'nom' => 'Test',
            'telephone' => '770000011',
        ]);

        $this->withToken(app(JwtService::class)->issue($user))
            ->putJson('/api/v1/utilisateurs/me', [
                'prenom' => 'Agent',
                'nom' => 'Test',
                'telephone' => '770000010',
                'whatsapp' => null,
            ])->assertBadRequest()
            ->assertJsonFragment(['USER_PHONE_ALREADY_USED']);
    }
}
