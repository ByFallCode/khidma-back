<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(['username' => '777197482'], [
            'password' => 'admin',
            'account_type' => 'ADMIN',
            'statut' => true,
            'prenom' => 'Admin',
            'nom' => 'Admin',
            'telephone' => '777197482',
        ]);

        foreach (['Magal de Touba', 'Magal Kaju Rajab', 'Touba Bootcamp 1ere edition'] as $label) {
            Event::query()->firstOrCreate(['libelle' => $label]);
        }
    }
}
