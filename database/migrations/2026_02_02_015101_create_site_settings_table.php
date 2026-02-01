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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            // --- IDENTITAS KOP SURAT ---
            $table->string('site_name')->default('SPBU-N NO. 7895101');
            $table->text('address')->default('Tumumpa Dua, Manado City, North Sulawesi');
            $table->string('logo_left')->nullable();
            $table->string('logo_right')->nullable();

            // --- NOTIFIKASI ---
            $table->string('notification_email')->nullable();
            $table->boolean('enable_email_notification')->default(false);
            $table->boolean('enable_web_notification')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
