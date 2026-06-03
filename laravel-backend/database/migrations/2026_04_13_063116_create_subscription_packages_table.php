<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // e.g., 'monthly', 'yearly', 'lifetime'
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->integer('duration_days')->nullable();
            $table->json('features')->nullable();
            $table->json('style')->nullable(); // UI presentation style (badge, color, etc.)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_packages');
    }
};
