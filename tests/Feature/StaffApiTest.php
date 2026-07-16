<?php

namespace Tests\Feature;

use App\Models\Pavilion;
use App\Models\Residence;
use App\Models\Room;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffApiTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private Residence $residence;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = $this->user('770000020', 'ADMIN');
        $manager = $this->user('770000021');
        $this->agent = $this->user('770000022');
        $this->residence = Residence::create([
            'libelle' => 'Résidence Équipe',
            'adresse' => 'Touba',
            'telephone_residence' => '338000020',
            'responsable_id' => $manager->id,
        ]);
        $this->token = app(JwtService::class)->issue($admin);
    }

    public function test_assignment_round_trip_and_agent_availability_metadata(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/assignments', [
            'agentId' => $this->agent->id,
            'residenceId' => $this->residence->id,
            'responsibilities' => ['ACCUEILLANT', 'CHEF_CHAMBRE'],
            'startDate' => '2026-07-13',
            'endDate' => '2026-07-20',
            'rotationSlots' => [
                ['dayOfWeek' => 'MONDAY', 'fromTime' => '08:00', 'toTime' => '12:00'],
                ['dayOfWeek' => null, 'fromTime' => '14:00', 'toTime' => '18:00'],
            ],
        ])->assertOk()
            ->assertJsonPath('agent.id', $this->agent->id)
            ->assertJsonPath('residence.id', $this->residence->id)
            ->assertJsonPath('rotationSlots.0.fromTime', '08:00')
            ->assertJsonCount(2, 'responsibilities');

        $assignmentId = $response->json('id');

        $this->assertDatabaseHas('hosts', [
            'user_id' => $this->agent->id,
            'residence_id' => $this->residence->id,
        ]);

        $this->withToken($this->token)->getJson("/api/v1/assignments?residenceId={$this->residence->id}&page=0&size=20")
            ->assertOk()->assertJsonPath('totalElements', 1);

        $this->withToken($this->token)->getJson('/api/v1/utilisateurs?page=0&size=50&accountType=KHIDMA_AGENT')
            ->assertOk()->assertJsonFragment([
                'id' => $this->agent->id,
                'hasAssignment' => true,
                'assignedResidenceName' => 'Résidence Équipe',
            ]);

        $this->withToken($this->token)->putJson("/api/v1/assignments/{$assignmentId}", [
            'agentId' => $this->agent->id,
            'residenceId' => $this->residence->id,
            'responsibilities' => ['RESPONSABLE_DELEGATION'],
            'rotationSlots' => [],
        ])->assertOk()
            ->assertJsonPath('responsibilities.0', 'RESPONSABLE_DELEGATION')
            ->assertJsonCount(0, 'rotationSlots');

        $this->assertDatabaseCount('assignment_responsibilities', 1);
        $this->assertDatabaseCount('rotation_slots', 0);
    }

    public function test_host_creation_lookup_update_and_delete_are_compatible(): void
    {
        $created = $this->withToken($this->token)->postJson('/api/v1/accueillants', [
            'utilisateur' => ['prenom' => 'Fatou', 'nom' => 'Ndiaye', 'telephone' => '771234567'],
            'residence' => ['id' => $this->residence->id],
        ])->assertOk()
            ->assertJsonPath('utilisateur.username', '771234567')
            ->assertJsonPath('residence.libelle', 'Résidence Équipe');

        $hostId = $created->json('id');
        $userId = $created->json('utilisateur.id');

        $this->withToken($this->token)->getJson('/api/v1/accueillants/user/771234567')
            ->assertOk()->assertJsonPath('id', $hostId);

        $this->withToken($this->token)->putJson('/api/v1/accueillants', [
            'id' => $hostId,
            'utilisateur' => ['prenom' => 'Fatou', 'nom' => 'Diop', 'telephone' => '771234568'],
            'residence' => ['id' => $this->residence->id],
        ])->assertOk()->assertJsonPath('utilisateur.nom', 'Diop');

        $this->withToken($this->token)->deleteJson("/api/v1/accueillants/{$hostId}")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_assignment_only_creates_host_for_accueillant_responsibility(): void
    {
        $this->withToken($this->token)->postJson('/api/v1/assignments', [
            'agentId' => $this->agent->id,
            'residenceId' => $this->residence->id,
            'responsibilities' => ['CHEF_CHAMBRE'],
            'rotationSlots' => [],
        ])->assertOk();

        $this->assertDatabaseMissing('hosts', [
            'user_id' => $this->agent->id,
            'residence_id' => $this->residence->id,
        ]);
    }

    public function test_room_manager_can_be_linked_to_rooms_and_filtered(): void
    {
        $pavilion = Pavilion::create(['libelle' => 'Pavillon A', 'niveau' => 1, 'residence_id' => $this->residence->id]);
        $room = Room::create([
            'nombre_place' => 4,
            'numero' => '01',
            'niveau' => 0,
            'reference' => 'PA-001',
            'pavilion_id' => $pavilion->id,
        ]);

        $this->withToken($this->token)->postJson('/api/v1/responsables', [
            'prenom' => 'Moussa',
            'nom' => 'Fall',
            'telephone' => '776543210',
            'residence' => ['id' => $this->residence->id],
            'chambres' => [['id' => $room->id]],
        ])->assertOk()
            ->assertJsonPath('residence.id', $this->residence->id)
            ->assertJsonPath('chambres', null);

        $this->assertDatabaseHas('room_manager_room', ['room_id' => $room->id]);

        $this->withToken($this->token)->getJson("/api/v1/responsables?page=0&size=200&residence={$this->residence->id}&search=Fall")
            ->assertOk()
            ->assertJsonPath('size', 200)
            ->assertJsonPath('totalElements', 1)
            ->assertJsonPath('content.0.telephone', '776543210');
    }

    public function test_room_manager_cannot_receive_a_room_from_another_residence(): void
    {
        $otherResidence = Residence::create([
            'libelle' => 'Autre résidence',
            'adresse' => 'Touba',
            'telephone_residence' => '338000099',
            'responsable_id' => $this->agent->id,
        ]);
        $otherPavilion = Pavilion::create([
            'libelle' => 'Pavillon B',
            'niveau' => 1,
            'residence_id' => $otherResidence->id,
        ]);
        $otherRoom = Room::create([
            'nombre_place' => 2,
            'numero' => '02',
            'niveau' => 0,
            'reference' => 'PB-002',
            'pavilion_id' => $otherPavilion->id,
        ]);

        $this->withToken($this->token)->postJson('/api/v1/responsables', [
            'prenom' => 'Moussa',
            'nom' => 'Fall',
            'telephone' => '776543211',
            'residence' => ['id' => $this->residence->id],
            'chambres' => [['id' => $otherRoom->id]],
        ])->assertStatus(400)
            ->assertJsonPath('code', 'VALIDATION_INVALID_ENTITY')
            ->assertJsonPath('validationErrors.0.field', 'chambres')
            ->assertJsonPath('validationErrors.0.code', 'RESPONSABLE_ROOM_RESIDENCE_MISMATCH');

        $this->assertDatabaseMissing('room_managers', ['telephone' => '776543211']);
    }

    private function user(string $phone, string $type = 'KHIDMA_AGENT'): User
    {
        return User::create([
            'username' => $phone,
            'password' => 'secret',
            'account_type' => $type,
            'statut' => true,
            'prenom' => 'User',
            'nom' => $phone,
            'telephone' => $phone,
        ]);
    }
}
