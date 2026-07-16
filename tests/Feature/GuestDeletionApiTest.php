<?php

namespace Tests\Feature;

use App\Models\Delegation;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Pavilion;
use App\Models\Reservation;
use App\Models\Residence;
use App\Models\Room;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestDeletionApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = $this->createUser('770000040');
        $this->token = app(JwtService::class)->issue($admin);
    }

    public function test_guest_without_reservation_can_be_deleted(): void
    {
        $guest = $this->createGuest();

        $this->withToken($this->token)->deleteJson("/api/v1/invites/{$guest->id}")
            ->assertOk()
            ->assertContent('null');

        $this->assertDatabaseMissing('guests', ['id' => $guest->id]);
    }

    public function test_guest_with_reservation_cannot_be_deleted(): void
    {
        $guest = $this->createGuest();
        $this->createReservation($guest);

        $this->withToken($this->token)->deleteJson("/api/v1/invites/{$guest->id}")
            ->assertConflict()
            ->assertExactJson([
                'httpCode' => 409,
                'code' => 'INVITE_HAS_ACTIVITY',
                'message' => 'INVITE_HAS_ACTIVITY',
                'errors' => [],
                'validationErrors' => [],
            ]);

        $this->assertDatabaseHas('guests', ['id' => $guest->id]);
    }

    public function test_guest_deletion_requires_authentication(): void
    {
        $guest = $this->createGuest();

        $this->deleteJson("/api/v1/invites/{$guest->id}")->assertUnauthorized();
    }

    public function test_guest_deletion_returns_not_found_for_unknown_id(): void
    {
        $this->withToken($this->token)->deleteJson('/api/v1/invites/999')->assertNotFound();
    }

    private function createGuest(): Guest
    {
        $delegation = Delegation::create(['nom' => 'Délégation Test', 'nombre' => 1]);

        return Guest::create([
            'prenom' => 'Invité',
            'nom' => 'Test',
            'telephone' => '780000010',
            'delegation_id' => $delegation->id,
        ]);
    }

    private function createReservation(Guest $guest): Reservation
    {
        $responsable = $this->createUser('770000041');
        $residence = Residence::create([
            'libelle' => 'Résidence Test',
            'adresse' => 'Touba',
            'telephone_residence' => '338000040',
            'responsable_id' => $responsable->id,
        ]);
        $pavilion = Pavilion::create([
            'libelle' => 'Pavillon Test',
            'residence_id' => $residence->id,
        ]);
        $room = Room::create([
            'nombre_place' => 2,
            'numero' => '01',
            'reference' => 'PT-001',
            'pavilion_id' => $pavilion->id,
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
            'account_type' => 'ADMIN',
            'statut' => true,
            'prenom' => 'Admin',
            'nom' => 'Test',
            'telephone' => $telephone,
        ]);
    }
}
