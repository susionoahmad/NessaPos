<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('settings', 'cash_drawer_enabled')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('cash_drawer_enabled')->default(true)->after('print_session_slip');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('settings', 'cash_drawer_enabled')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('cash_drawer_enabled');
        });
    }
};
