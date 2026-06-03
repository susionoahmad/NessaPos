<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_links', function (Blueprint $table) {
            // Mengubah kolom image menjadi text agar bisa menampung URL marketplace yang panjang
            $table->text('image')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_links', function (Blueprint $table) {
            $table->string('image', 255)->nullable()->change();
        });
    }
};