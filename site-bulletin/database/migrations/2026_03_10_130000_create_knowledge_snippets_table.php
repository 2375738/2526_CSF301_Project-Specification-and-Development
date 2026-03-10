<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_snippets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('summary', 280)->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('audience')->default('all');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['audience', 'department_id', 'is_active'], 'knowledge_snippets_visibility_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_snippets');
    }
};
