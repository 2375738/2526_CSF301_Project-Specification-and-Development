<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('duplicate_of_id')->nullable()->constrained('tickets')->nullOnDelete();
            $t->string('priority')->default('medium')->index(); // low|medium|high|critical
            $t->string('status')->default('new')->index();     // new|triaged|in_progress|waiting_employee|resolved|closed|reopened|cancelled
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('location')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
