<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_responsibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->enum('responsibility', ['RESPONSABLE_RESIDENCE', 'ACCUEILLANT', 'RESPONSABLE_DELEGATION', 'CHEF_CHAMBRE']);
            $table->unique(['assignment_id', 'responsibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_responsibilities');
    }
};
