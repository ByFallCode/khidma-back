<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delegation extends Model
{
    protected $fillable = ['nom', 'nombre'];

    protected function casts(): array
    {
        return ['nombre' => 'integer'];
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }
}
