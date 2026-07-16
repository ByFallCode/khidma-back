<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $fillable = ['agent_id', 'residence_id', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function responsibilityRows(): HasMany
    {
        return $this->hasMany(AssignmentResponsibility::class);
    }

    public function rotationSlots(): HasMany
    {
        return $this->hasMany(RotationSlot::class);
    }
}
