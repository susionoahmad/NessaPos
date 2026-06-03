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
        Schema::table('orders', function (Blueprint $table) {
            // Index for daily stats and listing optimization
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::table('stock_mutations', function (Blueprint $table) {
            // Index for history/mutation logs
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::table('cashier_transactions', function (Blueprint $table) {
            // Index for session balance calculation
            $table->index(['session_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'created_at']);
        });

        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'created_at']);
        });
        
        Schema::table('cashier_transactions', function (Blueprint $table) {
            $table->dropIndex(['session_id', 'id']);
        });
    }
};
