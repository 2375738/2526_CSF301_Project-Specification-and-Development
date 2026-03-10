<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcement_reads', function (Blueprint $table) {
            $table->string('acknowledgement')->nullable()->after('read_at');
            $table->timestamp('acknowledged_at')->nullable()->after('acknowledgement');
            $table->index('acknowledgement');
        });
    }

    public function down(): void
    {
        Schema::table('announcement_reads', function (Blueprint $table) {
            $table->dropIndex(['acknowledgement']);
            $table->dropColumn(['acknowledgement', 'acknowledged_at']);
        });
    }
};
