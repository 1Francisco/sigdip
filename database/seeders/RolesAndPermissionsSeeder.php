<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $adminRole = Role::create(['name' => 'Administrador']);
        $medicoRole = Role::create(['name' => 'Medico_Campo']);

        // Create a test Admin user if it doesn't exist
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@sigdip.com'],
            [
                'name' => 'Administrador Central',
                'password' => bcrypt('password123'), // Change in production
            ]
        );
        $adminUser->assignRole($adminRole);

        // Create a test Medico user if it doesn't exist
        $medicoUser = User::firstOrCreate(
            ['email' => 'medico@sigdip.com'],
            [
                'name' => 'Médico Verificador de Prueba',
                'password' => bcrypt('password123'), // Change in production
            ]
        );
        $medicoUser->assignRole($medicoRole);
    }
}
