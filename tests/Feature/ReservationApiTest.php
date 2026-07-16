<?php

namespace Tests\Feature;

use App\Models\Delegation;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Host;
use App\Models\Pavilion;
use App\Models\Residence;
use App\Models\Room;
use App\Models\RoomManager;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private Guest $guestOne;

    private Guest $guestTwo;

    private Host $host;

    private Residence $residence;

    private Room $room;

    private RoomManager $roomManager;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = $this->user('770000040', 'ADMIN');
        $residenceManager = $this->user('770000041');
        $hostUser = $this->user('770000042');
        $this->residence = Residence::create([
            'libelle' => 'Résidence Réservations',
            'adresse' => 'Touba',
            'telephone_residence' => '338000040',
            'responsable_id' => $residenceManager->id,
        ]);
        $pavilion = Pavilion::create(['libelle' => 'Pavillon Nord', 'niveau' => 1, 'residence_id' => $this->residence->id]);
        $this->room = Room::create([
            'nombre_place' => 2,
            'numero' => '01',
            'niveau' => 0,
            'reference' => 'PN-001',
            'pavilion_id' => $pavilion->id,
        ]);
        $this->host = Host::create(['user_id' => $hostUser->id, 'residence_id' => $this->residence->id]);
        $this->roomManager = RoomManager::create([
            'prenom' => 'Responsable',
            'nom' => 'Chambre',
            'telephone' => '770000043',
            'residence_id' => $this->residence->id,
        ]);
        $delegation = Delegation::create(['nom' => 'Dakar', 'nombre' => 2]);
        $this->guestOne = Guest::create($this->guestData('Awa', '771100001', $delegation->id, true));
        $this->guestTwo = Guest::create($this->guestData('Moussa', '771100002', $delegation->id));
        $this->event = Event::create(['libelle' => 'Grand Magal']);
        $this->token = app(JwtService::class)->issue($admin);
    }

    public function test_batch_creation_listing_update_and_availability_are_compatible(): void
    {
        $created = $this->withToken($this->token)->postJson('/api/v1/reservations', $this->batchPayload())
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.evenement.id', $this->event->id)
            ->assertJsonPath('0.chambre.pavillon.residence.id', $this->residence->id)
            ->assertJsonPath('0.invite.delegation.nom', 'Dakar')
            ->assertJsonPath('0.accueillant.id', $this->host->id);

        $reservationId = $created->json('0.id');

        $this->withToken($this->token)->getJson("/api/v1/reservations?page=0&size=20&year=2026&event={$this->event->id}&residence={$this->residence->id}&presence=1")
            ->assertOk()
            ->assertJsonPath('totalElements', 1)
            ->assertJsonPath('content.0.invite.telephone', $this->guestOne->telephone);

        $this->withToken($this->token)->getJson("/api/v1/chambres/residence/{$this->residence->id}/disponible/2026-08-01/2026-08-03")
            ->assertOk()->assertJsonCount(0);

        $this->withToken($this->token)->putJson('/api/v1/reservations', [
            'id' => $reservationId,
            'dateEntree' => '2026-08-02',
            'dateSortie' => '2026-08-04',
            'presence' => false,
            'chambre' => ['id' => $this->room->id],
            'accueillant' => ['id' => $this->host->id],
            'responsable' => ['id' => $this->roomManager->id],
        ])->assertOk()
            ->assertJsonPath('presence', false)
            ->assertJsonPath('responsable.id', $this->roomManager->id)
            ->assertJsonPath('dateSortieProvisoire', '2026-08-04T00:00:00.000000Z');

        $this->withToken($this->token)->deleteJson("/api/v1/reservations/{$reservationId}")->assertOk();

        $this->withToken($this->token)->getJson("/api/v1/chambres/residence/{$this->residence->id}/disponible/2026-08-01/2026-08-03")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.placeReservee', 1);
    }

    public function test_capacity_is_checked_atomically_for_the_whole_batch(): void
    {
        $this->room->update(['nombre_place' => 1]);
        $payload = $this->batchPayload();

        $this->withToken($this->token)->postJson('/api/v1/reservations', $payload)
            ->assertStatus(400)
            ->assertJsonFragment(['RESERVATION_ROOM_CAPACITY_EXCEEDED']);

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_room_availability_accepts_localized_javascript_dates(): void
    {
        $from = rawurlencode('Mon Jul 20 2026 00:00:00 GMT+0000 (heure moyenne de Greenwich)');
        $to = rawurlencode('Sat Jul 25 2026 00:00:00 GMT+0000 (heure moyenne de Greenwich)');

        $this->withToken($this->token)
            ->getJson("/api/v1/chambres/residence/{$this->residence->id}/disponible/{$from}/{$to}")
            ->assertOk()
            ->assertJsonPath('0.id', $this->room->id);
    }

    public function test_reservation_validation_keeps_legacy_codes(): void
    {
        $this->withToken($this->token)->postJson('/api/v1/reservations', ['invites' => []])
            ->assertStatus(400)
            ->assertJsonFragment(['RESERVATION_PERIOD_REQUIRED'])
            ->assertJsonFragment(['RESERVATION_EVENT_REQUIRED'])
            ->assertJsonFragment(['RESERVATION_GUESTS_REQUIRED']);
    }

    private function batchPayload(): array
    {
        return [
            'period' => ['entree' => '2026-08-01', 'sortie' => '2026-08-03'],
            'evenement' => ['id' => $this->event->id],
            'invites' => [
                $this->reservationGuest($this->guestOne, true),
                $this->reservationGuest($this->guestTwo, false),
            ],
        ];
    }

    private function reservationGuest(Guest $guest, bool $presence): array
    {
        return [
            'prenom' => $guest->prenom,
            'nom' => $guest->nom,
            'telephone' => $guest->telephone,
            'chambre' => ['id' => $this->room->id],
            'accueillant' => ['id' => $this->host->id],
            'presence' => $presence,
        ];
    }

    private function guestData(string $firstName, string $phone, int $delegation, bool $leader = false): array
    {
        return [
            'prenom' => $firstName,
            'nom' => 'Test',
            'telephone' => $phone,
            'est_responsable' => $leader,
            'delegation_id' => $delegation,
        ];
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
