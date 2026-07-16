<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'date_entree', 'date_sortie', 'date_sortie_provisoire', 'statut', 'presence',
        'event_id', 'room_id', 'guest_id', 'host_id', 'room_manager_id',
    ];

    protected function casts(): array
    {
        return [
            'date_entree' => 'datetime',
            'date_sortie' => 'datetime',
            'date_sortie_provisoire' => 'datetime',
            'statut' => 'boolean',
            'presence' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }

    public function roomManager(): BelongsTo
    {
        return $this->belongsTo(RoomManager::class);
    }
}
