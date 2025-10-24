<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions grouped by domain for clarity
        $permissionGroups = [
            'users' => ['user.view', 'user.create', 'user.edit', 'user.delete'],
            'employees' => ['employee.view', 'employee.create', 'employee.edit', 'employee.delete'],
            'grades' => ['grade.view', 'grade.create', 'grade.edit', 'grade.delete'],
            'work_units' => ['work_unit.view', 'work_unit.create', 'work_unit.edit', 'work_unit.delete'],
            'incoming_letters' => [
                'incoming_letter.view',
                'incoming_letter.create',
                'incoming_letter.edit',
                'incoming_letter.delete',
                'incoming_letter.dispose',
                'incoming_letter.reject',
                'incoming_letter.archive'
            ],
            'dispositions' => [
                'disposition.view',
                'disposition.create',
                'disposition.claim',
                'disposition.follow_up',
                'disposition.reject',
                'disposition.complete'
            ],
            'integration_logs' => [
                'integration_log.view'
            ],
        ];

        $allPermissions = collect($permissionGroups)->flatten()->unique()->values();

        foreach ($allPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Define roles (final set): superadmin, admin, pimpinan, staff
        // superadmin: all permissions
        // admin: manage users & organizational data + view/archive/reject letters, not necessarily dispose/follow-up
        // pimpinan: disposition decision & letter lifecycle actions (dispose/reject/archive) + view
        // staff: limited to viewing letters & working on dispositions (claim/follow_up)
        $roles = [
            'superadmin' => $allPermissions->merge(['integration_log.view'])->unique(),
            'admin' => [
                // user & organization management
                'user.view',
                'user.create',
                'user.edit',
                'user.delete',
                'employee.view',
                'employee.create',
                'employee.edit',
                'employee.delete',
                'grade.view',
                'grade.create',
                'grade.edit',
                'grade.delete',
                'work_unit.view',
                'work_unit.create',
                'work_unit.edit',
                'work_unit.delete',
                // incoming letters (no dispose action)
                'incoming_letter.view',
                'incoming_letter.create',
                'incoming_letter.edit',
                'incoming_letter.delete',
                'incoming_letter.reject',
                'incoming_letter.archive',
            ],
            'pimpinan' => [
                'incoming_letter.view',
                'incoming_letter.dispose',
                'incoming_letter.reject',
                'incoming_letter.archive',
                'disposition.view',
                'disposition.create',
                'disposition.reject',
                'disposition.complete',
            ],
            'staff' => [
                'incoming_letter.view',
                'disposition.view',
                'disposition.claim',
                'disposition.follow_up'
            ],
        ];

        // Optional cleanup: remove deprecated roles if they exist (only operator & legacy leader if present)
        $deprecated = ['operator', 'leader'];
        foreach ($deprecated as $oldRole) {
            $r = Role::where('name', $oldRole)->first();
            if ($r) {
                $r->delete();
            }
        }

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
