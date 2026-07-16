<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pavilions', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->unsignedInteger('niveau')->default(0);
            $table->boolean('archive')->default(false);
            $table->foreignId('residence_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pavilions');
    }
};
