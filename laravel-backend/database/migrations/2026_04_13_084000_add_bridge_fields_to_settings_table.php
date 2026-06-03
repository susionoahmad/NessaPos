<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('bridge_token')->nullable()->after('print_session_slip');
            $table->integer('bridge_port')->default(12348)->after('bridge_token');
            $table->string('allowed_origins')->default('*')->after('bridge_port');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['bridge_token', 'bridge_port', 'allowed_origins']);
        });
    }
};
