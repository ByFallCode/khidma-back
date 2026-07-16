<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_entree');
            $table->dateTime('date_sortie');
            $table->dateTime('date_sortie_provisoire');
            $table->boolean('statut')->nullable();
            $table->boolean('presence')->nullable();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->foreignId('guest_id')->constrained('guests')->restrictOnDelete();
            $table->foreignId('host_id')->nullable()->constrained('hosts')->nullOnDelete();
            $table->foreignId('room_manager_id')->nullable()->constrained('room_managers')->nullOnDelete();
            $table->timestamps();

            $table->index(['room_id', 'date_entree', 'date_sortie']);
            $table->index(['event_id', 'date_entree']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
