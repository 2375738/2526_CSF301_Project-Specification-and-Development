<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('notified_first_response_breach')->default(false)->after('sla_resolution_breached');
            $table->boolean('notified_resolution_breach')->default(false)->after('notified_first_response_breach');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['notified_first_response_breach', 'notified_resolution_breach']);
        });
    }
};
