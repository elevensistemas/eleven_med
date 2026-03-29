<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        $admin = Role::create(['name' => 'administrador']);
        $receptionist = Role::create(['name' => 'recepcionista']);
        $doctor = Role::create(['name' => 'médico']);
        $supervisor = Role::create(['name' => 'supervisor']);

        // Create basic permissions just for example
        // We can add granular ones if necessary
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage patients']);
        Permission::create(['name' => 'manage agenda']);
        Permission::create(['name' => 'manage chat']);

        // Assign all permissions to admin
        $admin->givePermissionTo(Permission::all());

        // Receptionist can only manage patients and agenda and chat
        $receptionist->givePermissionTo(['manage patients', 'manage agenda', 'manage chat']);

        // Create Admin User
        $superAdmin = User::firstOrCreate([
            'email' => 'admin@cortalezzi.com',
        ], [
            'name' => 'Administrador Cortalezzi',
            'password' => Hash::make('12345678'),
        ]);
        $superAdmin->assignRole($admin);

        $demoDoctor = User::firstOrCreate([
            'email' => 'medico@cortalezzi.com',
        ], [
            'name' => 'Dr. Cortalezzi',
            'password' => Hash::make('12345678'),
        ]);
        $demoDoctor->assignRole($doctor);
    }
}
