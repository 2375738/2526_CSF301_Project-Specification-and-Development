<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'duplicate_of_id')) {
                $table->foreignId('duplicate_of_id')
                    ->nullable()
                    ->after('category_id')
                    ->constrained('tickets')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'duplicate_of_id')) {
                $table->dropConstrainedForeignId('duplicate_of_id');
            }
        });
    }
};
