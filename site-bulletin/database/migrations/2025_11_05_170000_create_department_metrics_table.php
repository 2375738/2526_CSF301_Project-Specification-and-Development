<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->date('metric_date');
            $table->unsignedInteger('open_tickets')->default(0);
            $table->unsignedInteger('sla_breaches')->default(0);
            $table->unsignedInteger('messages_sent')->default(0);
            $table->unsignedInteger('avg_first_response_minutes')->nullable();
            $table->unsignedInteger('avg_resolution_minutes')->nullable();
            $table->timestamps();

            $table->unique(['department_id', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_metrics');
    }
};
