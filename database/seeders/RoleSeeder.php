<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'agente', 'cliente'] as $rol) {
            Role::findOrCreate($rol, 'api');
        }
    }
}
