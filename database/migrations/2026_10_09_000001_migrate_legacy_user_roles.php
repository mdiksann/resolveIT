<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'USER')->update(['role' => Role::Employee->value]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 16)->default(Role::Employee->value)->change();
        });
    }

    public function down(): void
    {
        // The legacy application has no Agent role; both non-admin roles become USER.
        DB::table('users')->whereIn('role', [Role::Employee->value, Role::Agent->value])->update(['role' => 'USER']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 16)->default('USER')->change();
        });
    }
};
