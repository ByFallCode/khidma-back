<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residences', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('adresse');
            $table->string('telephone_residence');
            $table->boolean('archive')->default(false);
            $table->foreignId('image_id')->nullable()->unique()->constrained('resources')->nullOnDelete();
            $table->foreignId('responsable_id')->unique()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residences');
    }
};
