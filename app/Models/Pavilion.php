<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pavilion extends Model
{
    protected $fillable = ['libelle', 'niveau', 'archive', 'residence_id'];

    protected function casts(): array
    {
        return ['niveau' => 'integer', 'archive' => 'boolean'];
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
