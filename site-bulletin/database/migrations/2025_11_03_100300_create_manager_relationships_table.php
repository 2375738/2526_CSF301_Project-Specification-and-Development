<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reports_to_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship_type')->default('direct');
            $table->timestamps();

            $table->unique(['manager_id', 'reports_to_id', 'relationship_type'], 'manager_relationship_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_relationships');
    }
};
