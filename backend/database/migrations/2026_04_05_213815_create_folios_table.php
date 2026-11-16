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
        Schema::create('folios', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('currency', 3)->default('NGN');
            $table->string('label')->nullable();

            // Balance stored in smallest currency unit (kobo, cents, etc.)
            // to avoid floating-point drift. Never store money as DECIMAL.
            $table->unsignedBigInteger('balance_minor')->default(0);

            // Optimistic lock counter — incremented on every balance write.
            $table->unsignedInteger('version')->default(1);

            $table->string('status')->default('active');

            $table->timestamps();

            $table->softDeletes();

            $table->index(['user_id', 'currency']);
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folios');
    }
};
