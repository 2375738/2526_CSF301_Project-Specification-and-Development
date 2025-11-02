<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('performance_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('week_start')->index();
            $t->unsignedInteger('units_per_hour')->nullable();
            $t->unsignedTinyInteger('rank_percentile')->nullable(); // 0 best .. 100 worst
            $t->timestamps();
            $t->unique(['user_id','week_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_snapshots');
    }
};
