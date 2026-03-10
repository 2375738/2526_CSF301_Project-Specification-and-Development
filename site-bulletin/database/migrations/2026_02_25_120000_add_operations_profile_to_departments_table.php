<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('ops_code', 16)->nullable()->after('color');
            $table->unsignedInteger('planned_headcount')->nullable()->after('ops_code');
            $table->decimal('target_units_per_hour', 6, 2)->nullable()->after('planned_headcount');
            $table->decimal('target_quality_pct', 5, 2)->nullable()->after('target_units_per_hour');
            $table->time('day_shift_start')->nullable()->after('target_quality_pct');
            $table->time('day_shift_end')->nullable()->after('day_shift_start');
            $table->time('night_shift_start')->nullable()->after('day_shift_end');
            $table->time('night_shift_end')->nullable()->after('night_shift_start');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn([
                'ops_code',
                'planned_headcount',
                'target_units_per_hour',
                'target_quality_pct',
                'day_shift_start',
                'day_shift_end',
                'night_shift_start',
                'night_shift_end',
            ]);
        });
    }
};

