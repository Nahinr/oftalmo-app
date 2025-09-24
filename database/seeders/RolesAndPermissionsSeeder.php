<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpia caché interno de Spatie (evita problemas de permisos)
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // 1) Lista de permisos base del sistema
        $perms = [
            // Usuarios / Roles
            'user.view','user.create','user.update','user.delete',
            'role.view','role.create','role.update','role.delete',
            'permission.view',

            // Pacientes
            'patient.view','patient.create','patient.update','patient.delete',
            'patient.restore',       
            'patient.forceDelete',
            'patient.attachments.viewAny',
            'patient.attachments.view',
            'patient.attachments.create',
            'patient.attachments.update',
            'patient.attachments.delete',
            'patient.attachments.download',      

            // Citas (si aplica)
            'appointment.view','appointment.create','appointment.update','appointment.delete',
            'appointment.restore',
            'appointment.forceDelete',

            // Historial, etc. (igual patrón)
            'history.view','history.create','history.update','history.delete',
            'history.restore',
            'history.forceDelete',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // 2) Crear roles
        $admin  = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $doctor = Role::firstOrCreate(['name' => 'Doctor',        'guard_name' => 'web']);
        $recept = Role::firstOrCreate(['name' => 'Receptionist',  'guard_name' => 'web']);

        // 3) Asignar permisos a roles
        $admin->syncPermissions(Permission::all());

        $doctor->syncPermissions([
            'patient.view','patient.update','patient.create',
            'appointment.view','appointment.create','appointment.update',
            'history.view','history.create','history.update',
            'user.view','user.create','user.delete', 'patient.attachments.viewAny',
            'patient.attachments.view', 'patient.attachments.create', 'patient.attachments.update', 
            'patient.attachments.download',
        ]);

        $recept->syncPermissions([
            'patient.view','patient.create','patient.update',
            'appointment.view','appointment.create','appointment.update',
        ]);

        // 4) Asignar rol Administrator al usuario inicial
        $adminEmail = 'admin@admin.com'; // ajusta si tu admin tiene otro correo
        if ($u = User::where('email', $adminEmail)->first()) {
            $u->syncRoles(['Administrator']);
        }
    }
}
