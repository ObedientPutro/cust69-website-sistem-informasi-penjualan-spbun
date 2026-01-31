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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('manager_name');
            $table->string('owner_name');
            $table->string('ship_name');
            $table->string('ship_type');
            $table->decimal('gross_tonnage', 8, 2);
            $table->decimal('pk_engine', 8, 2);
            $table->string('phone');
            $table->text('address');
            $table->string('photo')->nullable();

            // Limit Piutang (Default 0 = Tidak boleh hutang sebelum diset)
            $table->decimal('credit_limit', 15, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
