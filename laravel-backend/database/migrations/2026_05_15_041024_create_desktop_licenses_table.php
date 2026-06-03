<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desktop_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('serial_key')->unique();
            $table->string('device_id')->nullable(); // Locked to this hardware on activation
            $table->string('licensee_name');
            $table->date('expiry_date')->nullable(); // Null for lifetime
            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desktop_licenses');
    }
};
