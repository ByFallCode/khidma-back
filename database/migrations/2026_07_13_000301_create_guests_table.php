<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('prenom', 40);
            $table->string('nom', 20);
            $table->string('telephone', 15)->unique();
            $table->string('adresse', 90)->nullable();
            $table->string('email', 90)->nullable();
            $table->boolean('est_responsable')->default(false);
            $table->foreignId('delegation_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
