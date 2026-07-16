<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomManager extends Model
{
    protected $fillable = ['prenom', 'nom', 'telephone', 'residence_id'];

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_manager_room');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
