<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('order')
                ->constrained('departments')
                ->nullOnDelete();

            $table->string('audience')->default('all')->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('audience');
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
