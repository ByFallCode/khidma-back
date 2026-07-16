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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_residence_totals_and_available_rooms_match_the_dashboard_contract(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00');
        $admin = $this->user('770000050', 'ADMIN');
        $manager = $this->user('770000051');
        $residence = Residence::create([
            'libelle' => 'Résidence Statistiques',
            'adresse' => 'Touba',
            'telephone_residence' => '338000050',
            'responsable_id' => $manager->id,
        ]);
        $north = Pavilion::create(['libelle' => 'Pavillon Nord', 'niveau' => 0, 'residence_id' => $residence->id]);
        $south = Pavilion::create(['libelle' => 'Pavillon Sud', 'niveau' => 0, 'residence_id' => $residence->id]);
        $fullRoom = $this->room($north, 'PN-001', 1);
        $this->room($north, 'PN-002', 2);
        $this->room($south, 'PS-001', 1);
        $delegation = Delegation::create(['nom' => 'Dakar', 'nombre' => 2]);
        $guestOne = $this->guest($delegation, 'Awa', '771200001');
        $guestTwo = $this->guest($delegation, 'Moussa', '771200002');
        $event = Event::create(['libelle' => 'Grand Magal']);
        Reservation::create([
            'date_entree' => '2026-07-14',
            'date_sortie' => '2026-07-20',
            'date_sortie_provisoire' => '2026-07-20',
            'event_id' => $event->id,
            'room_id' => $fullRoom->id,
            'guest_id' => $guestOne->id,
        ]);
        Reservation::create([
            'date_entree' => '2026-09-01',
            'date_sortie' => '2026-09-03',
            'date_sortie_provisoire' => '2026-09-03',
            'event_id' => $event->id,
            'room_id' => $fullRoom->id,
            'guest_id' => $guestTwo->id,
        ]);
        $token = app(JwtService::class)->issue($admin);

        $this->withToken($token)->getJson("/api/v1/stats/{$residence->id}")
            ->assertOk()
            ->assertExactJson([
                'pavillons' => 2,
                'chambres' => 3,
                'delegations' => 1,
                'reservations' => 2,
            ]);

        $this->withToken($token)->getJson("/api/v1/stats/{$residence->id}/chambres")
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['pavillon' => 'Pavillon Nord', 'chambres' => 1])
            ->assertJsonFragment(['pavillon' => 'Pavillon Sud', 'chambres' => 1]);

        $query = "year=2026&event={$event->id}&presence=-1&locale=fr";
        $excel = $this->withToken($token)->get("/api/v1/reservations/exportation/residence/{$residence->id}?{$query}")
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $excel->streamedContent());

        $pdf = $this->withToken($token)->get("/api/v1/reservations/exportation/pdf/residence/{$residence->id}?{$query}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->streamedContent());
    }

    private function room(Pavilion $pavilion, string $reference, int $capacity): Room
    {
        return Room::create([
            'nombre_place' => $capacity,
            'numero' => substr($reference, -2),
            'niveau' => 0,
            'reference' => $reference,
            'pavilion_id' => $pavilion->id,
        ]);
    }

    private function guest(Delegation $delegation, string $firstName, string $phone): Guest
    {
        return Guest::create([
            'prenom' => $firstName,
            'nom' => 'Test',
            'telephone' => $phone,
            'delegation_id' => $delegation->id,
        ]);
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
