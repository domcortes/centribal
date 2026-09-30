<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin de prueba',
            'email' => 'admin@centribal.com',
        ]);

        User::factory()->agente()->create([
            'name' => 'Agente Uno',
            'email' => 'agente1@centribal.com',
        ]);

        User::factory()->agente()->create([
            'name' => 'Agente Dos',
            'email' => 'agente2@centribal.com',
        ]);

        User::factory()->create([
            'name' => 'Cliente Uno',
            'email' => 'cliente1@centribal.com',
        ]);

        User::factory()->create([
            'name' => 'Cliente Dos',
            'email' => 'cliente2@centribal.com',
        ]);
    }
}
