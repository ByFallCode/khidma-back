<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_manager_room', function (Blueprint $table) {
            $table->foreignId('room_manager_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->primary(['room_manager_id', 'room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_manager_room');
    }
};
