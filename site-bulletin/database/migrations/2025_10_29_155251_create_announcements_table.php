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
        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body')->nullable();
            $t->timestamp('starts_at')->nullable()->index();
            $t->timestamp('ends_at')->nullable()->index();
            $t->boolean('is_pinned')->default(false)->index();
            $t->boolean('is_active')->default(true)->index();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
