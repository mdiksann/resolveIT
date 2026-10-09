<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->integer('rank');
            $table->integer('sla_hours');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE priorities ADD CONSTRAINT priorities_sla_hours_positive CHECK (sla_hours > 0)');
        DB::statement('ALTER TABLE priorities ADD CONSTRAINT priorities_rank_positive CHECK (rank > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};
