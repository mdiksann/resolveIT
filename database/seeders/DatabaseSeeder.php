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
        foreach (['admin' => Role::Admin, 'user' => Role::Employee] as $name => $role) {
            User::firstOrCreate(['email' => $name.'@example.test'], [
                'name' => ucfirst($name),
                'password' => 'local-password',
            ])->forceFill(['role' => $role])->save();
        }
    }
}
