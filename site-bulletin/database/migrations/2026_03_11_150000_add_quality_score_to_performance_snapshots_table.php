<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_snapshots', function (Blueprint $table) {
            $table->decimal('quality_score', 5, 1)->nullable()->after('rank_percentile');
        });
    }

    public function down(): void
    {
        Schema::table('performance_snapshots', function (Blueprint $table) {
            $table->dropColumn('quality_score');
        });
    }
};
