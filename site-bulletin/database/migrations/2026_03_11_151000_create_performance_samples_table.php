<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('recorded_at')->index();
            $table->decimal('units_per_hour', 6, 1);
            $table->decimal('quality_score', 5, 1);
            $table->timestamps();

            $table->unique(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_samples');
    }
};
