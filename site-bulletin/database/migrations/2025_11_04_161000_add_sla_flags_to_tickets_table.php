<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('sla_first_response_breached')->default(false)->after('closed_at');
            $table->boolean('sla_resolution_breached')->default(false)->after('sla_first_response_breached');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['sla_first_response_breached', 'sla_resolution_breached']);
        });
    }
};
