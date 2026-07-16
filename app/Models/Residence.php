<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Residence extends Model
{
    protected $fillable = ['libelle', 'adresse', 'telephone_residence', 'archive', 'image_id', 'responsable_id'];

    protected function casts(): array
    {
        return ['archive' => 'boolean'];
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'image_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function pavilions(): HasMany
    {
        return $this->hasMany(Pavilion::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function hosts(): HasMany
    {
        return $this->hasMany(Host::class);
    }

    public function roomManagers(): HasMany
    {
        return $this->hasMany(RoomManager::class);
    }
}
