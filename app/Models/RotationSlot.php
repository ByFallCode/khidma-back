<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotationSlot extends Model
{
    protected $fillable = ['day_of_week', 'from_time', 'to_time'];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
}
