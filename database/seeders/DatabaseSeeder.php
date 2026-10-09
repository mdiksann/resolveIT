<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }
        foreach (['employee' => Role::Employee, 'agent' => Role::Agent, 'admin' => Role::Admin] as $name => $role) {
            $user = User::firstOrNew(['email' => $name.'@example.test']);
            if ($user->exists) {
                continue;
            }
            $user->fill([
                'name' => ucfirst($name),
                'password' => 'local-password',
            ]);
            $user->role = $role;
            $user->save();
        }
    }
}
