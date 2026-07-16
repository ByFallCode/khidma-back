<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['nombre_place', 'numero', 'niveau', 'reference', 'archive', 'pavilion_id'];

    protected function casts(): array
    {
        return ['nombre_place' => 'integer', 'niveau' => 'integer', 'archive' => 'boolean'];
    }

    public function pavilion(): BelongsTo
    {
        return $this->belongsTo(Pavilion::class);
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(RoomManager::class, 'room_manager_room');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
