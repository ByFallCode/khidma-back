<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = ['libelle', 'date_debut', 'date_fin'];

    protected function casts(): array
    {
        return ['date_debut' => 'date:Y-m-d', 'date_fin' => 'date:Y-m-d'];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
