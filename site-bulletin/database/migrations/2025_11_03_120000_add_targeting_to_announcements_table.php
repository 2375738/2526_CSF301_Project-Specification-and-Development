<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('author_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->after('author_id')
                ->constrained('departments')
                ->nullOnDelete();

            $table->string('audience')->default('all')->after('department_id');
            $table->index(['audience', 'department_id'], 'announcements_audience_department_index');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex('announcements_audience_department_index');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('author_id');
            $table->dropColumn('audience');
        });
    }
};
