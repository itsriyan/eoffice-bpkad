<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Grade;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\Hash;

class UsersAndEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure base grades & work units exist for foreign keys (simple fallback)
        $defaultGrade = Grade::first();
        if (!$defaultGrade) {
            $defaultGrade = Grade::create([
                'code' => 'III/a',
                'category' => 'Golongan III',
                'rank' => 'Penata Muda'
            ]);
        }

        $defaultUnit = WorkUnit::first();
        if (!$defaultUnit) {
            $defaultUnit = WorkUnit::create([
                'name' => 'Sekretariat',
                'description' => 'Unit default'
            ]);
        }

        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@example.com', 'role' => 'superadmin', 'position' => 'Super Administrator'],
            ['name' => 'Admin Sistem', 'email' => 'admin@example.com', 'role' => 'admin', 'position' => 'Administrator'],
            ['name' => 'Pimpinan Utama', 'email' => 'pimpinan@example.com', 'role' => 'pimpinan', 'position' => 'Pimpinan'],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            if (!$user->hasRole($u['role'])) {
                $user->assignRole($u['role']);
            }

            // Create or update employee profile
            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'grade_id' => $defaultGrade->id,
                    'work_unit_id' => $defaultUnit->id,
                    'name' => $user->name,
                    'nip' => $this->generateNip($user->id),
                    'position' => $u['position'],
                    'email' => $user->email,
                    'phone_number' => $this->fakePhone(),
                    'status' => 'active',
                ]
            );
        }

        // Seed multiple staff users
        for ($i = 1; $i <= 5; $i++) {
            $email = 'staff' . $i . '@example.com';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Staff ' . $i,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            if (!$user->hasRole('staff')) {
                $user->assignRole('staff');
            }
            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'grade_id' => $defaultGrade->id,
                    'work_unit_id' => $defaultUnit->id,
                    'name' => $user->name,
                    'nip' => $this->generateNip($user->id),
                    'position' => 'Staf Pelaksana',
                    'email' => $user->email,
                    'phone_number' => $this->fakePhone(),
                    'status' => 'active',
                ]
            );
        }
    }

    private function generateNip(int $seed): string
    {
        return str_pad((string) $seed, 18, '0', STR_PAD_LEFT);
    }

    private function fakePhone(): string
    {
        // Basic Indonesian phone pattern 62812xxxxxxx
        return '62812' . random_int(1000000, 9999999);
    }
}
