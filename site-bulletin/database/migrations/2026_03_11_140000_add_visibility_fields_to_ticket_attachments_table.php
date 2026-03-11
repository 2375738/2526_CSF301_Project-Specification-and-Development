<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->string('visibility')->default('public')->after('size');
            $table->string('kind')->default('other')->after('visibility');
            $table->string('label')->nullable()->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'kind', 'label']);
        });
    }
};
