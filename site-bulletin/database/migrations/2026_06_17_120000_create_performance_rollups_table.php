<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bucket_type', 16);
            $table->dateTime('bucket_start');
            $table->unsignedInteger('sample_count');
            $table->decimal('avg_units_per_hour', 6, 1);
            $table->decimal('avg_quality_score', 5, 1);
            $table->timestamps();

            $table->unique(['user_id', 'bucket_type', 'bucket_start']);
            $table->index(['bucket_type', 'bucket_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_rollups');
    }
};
