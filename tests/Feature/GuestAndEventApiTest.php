<?php

namespace Tests\Feature;

use App\Models\Delegation;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestAndEventApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'username' => '770000030',
            'password' => 'secret',
            'account_type' => 'ADMIN',
            'statut' => true,
            'prenom' => 'Admin',
            'nom' => 'Test',
            'telephone' => '770000030',
        ]);
        $this->token = app(JwtService::class)->issue($admin);
    }

    public function test_delegation_round_trip_preserves_leader_and_guests(): void
    {
        $created = $this->withToken($this->token)->postJson('/api/v1/delegations', [
            'nom' => 'Délégation de Dakar',
            'nombre' => 12,
            'chef' => $this->guest('Awa', 'Diop', '771000001'),
            'invites' => [$this->guest('Moussa', 'Fall', '771000002')],
        ])->assertOk()
            ->assertJsonPath('chef.estResponsable', true)
            ->assertJsonPath('chef.delegation.nom', 'Délégation de Dakar')
            ->assertJsonPath('invites.0.estResponsable', false)
            ->assertJsonCount(1, 'invites');

        $id = $created->json('id');

        $this->withToken($this->token)->getJson('/api/v1/delegations?page=0&size=10')
            ->assertOk()
            ->assertJsonPath('totalElements', 1)
            ->assertJsonPath('content.0.id', $id);

        $this->withToken($this->token)->putJson('/api/v1/delegations', [
            'id' => $id,
            'nom' => 'Délégation mise à jour',
            'nombre' => 15,
            'chef' => $created->json('chef'),
            'invites' => $created->json('invites'),
        ])->assertOk()
            ->assertJsonPath('nom', 'Délégation mise à jour')
            ->assertJsonCount(1, 'invites');

        $this->withToken($this->token)->deleteJson("/api/v1/delegations/{$id}")->assertOk();
        $this->assertDatabaseCount('guests', 0);
    }

    public function test_guest_can_be_added_to_an_existing_delegation(): void
    {
        $delegation = Delegation::create(['nom' => 'Thiès', 'nombre' => 4]);

        $this->withToken($this->token)->postJson('/api/v1/invites', [
            ...$this->guest('Sokhna', 'Ndiaye', '772000001'),
            'estResponsable' => false,
            'delegation' => ['id' => $delegation->id],
        ])->assertOk()
            ->assertJsonPath('delegation.id', $delegation->id)
            ->assertJsonPath('telephone', '772000001');

        $this->assertDatabaseHas('guests', ['telephone' => '772000001', 'delegation_id' => $delegation->id]);
    }

    public function test_event_post_creates_and_updates_the_same_resource(): void
    {
        $created = $this->withToken($this->token)->postJson('/api/v1/evenements', [
            'libelle' => 'Grand Magal',
            'dateDebut' => '2026-08-01',
            'dateFin' => '2026-08-03',
        ])->assertOk()->assertJsonPath('dateDebut', '2026-08-01');

        $id = $created->json('id');

        $this->withToken($this->token)->postJson('/api/v1/evenements', [
            'id' => $id,
            'libelle' => 'Grand Magal de Touba',
            'dateDebut' => '2026-08-02',
            'dateFin' => '2026-08-04',
        ])->assertOk()->assertJsonPath('libelle', 'Grand Magal de Touba');

        $this->withToken($this->token)->getJson('/api/v1/evenements')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $id);
    }

    public function test_validation_uses_legacy_error_codes(): void
    {
        $this->withToken($this->token)->postJson('/api/v1/delegations', [
            'nombre' => 0,
            'invites' => [],
        ])->assertStatus(400)
            ->assertJsonFragment(['DELEGATION_NAME_REQUIRED'])
            ->assertJsonFragment(['DELEGATION_LEADER_REQUIRED']);

        $this->withToken($this->token)->postJson('/api/v1/evenements', [
            'libelle' => 'Dates invalides',
            'dateDebut' => '2026-08-10',
            'dateFin' => '2026-08-09',
        ])->assertStatus(400);
    }

    private function guest(string $firstName, string $lastName, string $phone): array
    {
        return [
            'prenom' => $firstName,
            'nom' => $lastName,
            'telephone' => $phone,
            'adresse' => 'Touba',
            'email' => null,
        ];
    }
}
