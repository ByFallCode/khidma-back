<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentResponsibility extends Model
{
    public $timestamps = false;

    protected $fillable = ['responsibility'];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
}
