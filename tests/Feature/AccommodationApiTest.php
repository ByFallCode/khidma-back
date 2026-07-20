<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Host;
use App\Models\Pavilion;
use App\Models\Reservation;
use App\Models\Residence;
use App\Models\Room;
use App\Models\RoomManager;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccommodationApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $user = User::create([
            'username' => '770000010',
            'password' => 'secret',
            'account_type' => 'ADMIN',
            'statut' => true,
            'prenom' => 'Admin',
            'nom' => 'Test',
            'telephone' => '770000010',
        ]);
        $this->token = app(JwtService::class)->issue($user);
    }

    public function test_residence_creation_also_creates_manager_and_public_image(): void
    {
        $response = $this->withToken($this->token)->post('/api/v1/residences', [
            'libelle' => 'Résidence Firdawsi',
            'adresse' => 'Touba',
            'telephoneResidence' => '338000000',
            'prenom' => 'Awa',
            'nom' => 'Fall',
            'telephone' => '771111111',
            'image' => UploadedFile::fake()->image('residence.png'),
        ])->assertOk()
            ->assertJsonPath('responsable.username', '771111111')
            ->assertJsonPath('responsable.accountType', 'KHIDMA_AGENT')
            ->assertJsonPath('pavillons', []);

        $resourceId = $response->json('image.id');
        $this->get("/api/v1/ressources/{$resourceId}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->assertDatabaseHas('users', ['username' => '771111111']);
        $this->assertDatabaseHas('residences', ['libelle' => 'Résidence Firdawsi']);
    }

    public function test_pavilion_creation_can_create_rooms_and_references(): void
    {
        $residenceId = $this->createResidence();

        $response = $this->withToken($this->token)->postJson('/api/v1/pavillons', [
            'libelle' => 'Pavillon Bleu',
            'niveau' => 2,
            'residence' => ['id' => $residenceId],
            'chambres' => [
                ['numero' => '01', 'nombrePlace' => 4],
                ['numero' => '12', 'nombrePlace' => 2, 'niveau' => 1],
            ],
        ])->assertOk()
            ->assertJsonPath('chambres.0.reference', 'PB-001')
            ->assertJsonPath('chambres.1.reference', 'PB-112');

        $pavilionId = $response->json('id');
        $this->withToken($this->token)->getJson("/api/v1/chambres/pavillon/{$pavilionId}?page=0&size=1")
            ->assertOk()
            ->assertJsonPath('totalElements', 2)
            ->assertJsonPath('totalPages', 2)
            ->assertJsonCount(1, 'content');
    }

    public function test_standalone_room_uses_pavilion_initials(): void
    {
        $residenceId = $this->createResidence();
        $pavilion = $this->withToken($this->token)->postJson('/api/v1/pavillons', [
            'libelle' => 'Darou Salam',
            'niveau' => 3,
            'residence' => ['id' => $residenceId],
            'chambres' => [],
        ])->assertOk()->json();

        $this->withToken($this->token)->postJson('/api/v1/chambres', [
            'numero' => '7',
            'niveau' => 2,
            'nombrePlace' => 5,
            'pavillon' => $pavilion,
        ])->assertOk()
            ->assertJsonPath('reference', 'DS-27')
            ->assertJsonPath('placeReservee', 0)
            ->assertJsonPath('pavillon.residence.id', $residenceId);
    }

    public function test_residence_validation_uses_existing_error_codes(): void
    {
        $this->withToken($this->token)->postJson('/api/v1/residences', [])
            ->assertStatus(400)
            ->assertJsonPath('code', 'VALIDATION_INVALID_ENTITY')
            ->assertJsonFragment(['field' => 'libelle', 'code' => 'RESIDENCE_LABEL_REQUIRED']);
    }

    public function test_residence_update_also_updates_its_manager_information(): void
    {
        $residenceId = $this->createResidence();

        $this->withToken($this->token)->putJson('/api/v1/residences', [
            'id' => $residenceId,
            'libelle' => 'Résidence mise à jour',
            'adresse' => 'Darou Salam',
            'telephoneResidence' => '338000099',
            'prenom' => 'Fatou',
            'nom' => 'Diop',
            'telephone' => '779999999',
            'whatsapp' => '789999999',
        ])->assertOk()
            ->assertJsonPath('responsable.prenom', 'Fatou')
            ->assertJsonPath('responsable.nom', 'Diop')
            ->assertJsonPath('responsable.username', '779999999')
            ->assertJsonPath('responsable.telephone', '779999999')
            ->assertJsonPath('responsable.whatsapp', '789999999');

        $this->assertDatabaseHas('users', [
            'prenom' => 'Fatou',
            'nom' => 'Diop',
            'username' => '779999999',
            'telephone' => '779999999',
            'whatsapp' => '789999999',
        ]);
    }

    public function test_unused_room_can_be_deleted(): void
    {
        [, , $room] = $this->createAccommodation();

        $this->withToken($this->token)->deleteJson("/api/v1/chambres/{$room->id}")
            ->assertOk()
            ->assertContent('null');

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_reserved_room_cannot_be_deleted(): void
    {
        [, , $room] = $this->createAccommodation();
        $this->createReservation($room);

        $this->assertActivityConflict(
            $this->withToken($this->token)->deleteJson("/api/v1/chambres/{$room->id}"),
            'CHAMBRE_HAS_ACTIVITY',
        );
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_room_assigned_to_manager_cannot_be_deleted(): void
    {
        [$residence, , $room] = $this->createAccommodation();
        $manager = RoomManager::create([
            'prenom' => 'Responsable',
            'nom' => 'Chambre',
            'telephone' => '770000020',
            'residence_id' => $residence->id,
        ]);
        $manager->rooms()->attach($room);

        $this->assertActivityConflict(
            $this->withToken($this->token)->deleteJson("/api/v1/chambres/{$room->id}"),
            'CHAMBRE_HAS_ACTIVITY',
        );
    }

    public function test_pavilion_with_unused_rooms_can_be_deleted(): void
    {
        [, $pavilion, $room] = $this->createAccommodation();

        $this->withToken($this->token)->deleteJson("/api/v1/pavillons/{$pavilion->id}")
            ->assertOk()
            ->assertContent('null');

        $this->assertDatabaseMissing('pavilions', ['id' => $pavilion->id]);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_pavilion_containing_reserved_room_cannot_be_deleted(): void
    {
        [, $pavilion, $room] = $this->createAccommodation();
        $this->createReservation($room);

        $this->assertActivityConflict(
            $this->withToken($this->token)->deleteJson("/api/v1/pavillons/{$pavilion->id}"),
            'PAVILLON_HAS_ACTIVITY',
        );
        $this->assertDatabaseHas('pavilions', ['id' => $pavilion->id]);
    }

    public function test_residence_with_only_unused_pavilions_and_rooms_can_be_deleted(): void
    {
        [$residence, $pavilion, $room] = $this->createAccommodation();
        $responsableId = $residence->responsable_id;

        $this->withToken($this->token)->deleteJson("/api/v1/residences/{$residence->id}")
            ->assertOk()
            ->assertContent('null');

        $this->assertDatabaseMissing('residences', ['id' => $residence->id]);
        $this->assertDatabaseMissing('pavilions', ['id' => $pavilion->id]);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('users', ['id' => $responsableId]);
    }

    public function test_residence_with_assignment_cannot_be_deleted(): void
    {
        [$residence] = $this->createAccommodation();
        Assignment::create([
            'agent_id' => $this->createUser('770000021')->id,
            'residence_id' => $residence->id,
        ]);

        $this->assertResidenceActivityConflict($residence);
    }

    public function test_residence_with_host_cannot_be_deleted(): void
    {
        [$residence] = $this->createAccommodation();
        Host::create([
            'user_id' => $this->createUser('770000022')->id,
            'residence_id' => $residence->id,
        ]);

        $this->assertResidenceActivityConflict($residence);
    }

    public function test_residence_with_room_manager_cannot_be_deleted(): void
    {
        [$residence] = $this->createAccommodation();
        RoomManager::create([
            'prenom' => 'Responsable',
            'nom' => 'Residence',
            'telephone' => '770000023',
            'residence_id' => $residence->id,
        ]);

        $this->assertResidenceActivityConflict($residence);
    }

    public function test_residence_with_reservation_in_one_of_its_rooms_cannot_be_deleted(): void
    {
        [$residence, , $room] = $this->createAccommodation();
        $this->createReservation($room);

        $this->assertResidenceActivityConflict($residence);
    }

    public function test_deletion_routes_require_authentication(): void
    {
        [$residence, $pavilion, $room] = $this->createAccommodation();
        $this->withHeader('Authorization', '');

        $this->deleteJson("/api/v1/residences/{$residence->id}")->assertUnauthorized();
        $this->deleteJson("/api/v1/pavillons/{$pavilion->id}")->assertUnauthorized();
        $this->deleteJson("/api/v1/chambres/{$room->id}")->assertUnauthorized();
    }

    public function test_deletion_routes_return_not_found_for_unknown_ids(): void
    {
        $this->withToken($this->token)->deleteJson('/api/v1/residences/999')->assertNotFound();
        $this->withToken($this->token)->deleteJson('/api/v1/pavillons/999')->assertNotFound();
        $this->withToken($this->token)->deleteJson('/api/v1/chambres/999')->assertNotFound();
    }

    private function createResidence(): int
    {
        return $this->withToken($this->token)->post('/api/v1/residences', [
            'libelle' => 'Résidence Test',
            'adresse' => 'Touba',
            'telephoneResidence' => '338000001',
            'prenom' => 'Manager',
            'nom' => 'Test',
            'telephone' => '77'.random_int(2000000, 9999999),
        ])->assertOk()->json('id');
    }

    private function createAccommodation(): array
    {
        $residence = Residence::findOrFail($this->createResidence());
        $pavilion = Pavilion::create([
            'libelle' => 'Pavillon Test',
            'niveau' => 1,
            'residence_id' => $residence->id,
        ]);
        $room = Room::create([
            'nombre_place' => 4,
            'numero' => '01',
            'niveau' => 0,
            'reference' => 'PT-'.$residence->id,
            'pavilion_id' => $pavilion->id,
        ]);

        return [$residence, $pavilion, $room];
    }

    private function createReservation(Room $room): Reservation
    {
        $delegation = Delegation::create(['nom' => 'Délégation Test', 'nombre' => 1]);
        $guest = Guest::create([
            'prenom' => 'Invité',
            'nom' => 'Test',
            'telephone' => '780000001',
            'delegation_id' => $delegation->id,
        ]);
        $event = Event::create(['libelle' => 'Événement Test']);

        return Reservation::create([
            'date_entree' => '2026-07-14 10:00:00',
            'date_sortie' => '2026-07-15 10:00:00',
            'date_sortie_provisoire' => '2026-07-15 10:00:00',
            'event_id' => $event->id,
            'room_id' => $room->id,
            'guest_id' => $guest->id,
        ]);
    }

    private function createUser(string $telephone): User
    {
        return User::create([
            'username' => $telephone,
            'password' => 'secret',
            'account_type' => 'KHIDMA_AGENT',
            'statut' => true,
            'prenom' => 'Agent',
            'nom' => 'Test',
            'telephone' => $telephone,
        ]);
    }

    private function assertResidenceActivityConflict(Residence $residence): void
    {
        $this->assertActivityConflict(
            $this->withToken($this->token)->deleteJson("/api/v1/residences/{$residence->id}"),
            'RESIDENCE_HAS_ACTIVITY',
        );
        $this->assertDatabaseHas('residences', ['id' => $residence->id]);
    }

    private function assertActivityConflict($response, string $code): void
    {
        $response->assertConflict()->assertExactJson([
            'httpCode' => 409,
            'code' => $code,
            'message' => $code,
            'errors' => [],
            'validationErrors' => [],
        ]);
    }
}
