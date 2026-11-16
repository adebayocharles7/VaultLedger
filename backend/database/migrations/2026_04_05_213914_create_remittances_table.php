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
        Schema::create('remittances', function (Blueprint $table) {

            $table->ulid('id')->primary();

            // Nullable: null source = external funding; null destination = external withdrawal
            $table->foreignUlid('source_folio_id')
                  ->nullable()
                  ->constrained('folios')
                  ->nullOnDelete();

            $table->foreignUlid('destination_folio_id')
                  ->nullable()
                  ->constrained('folios')
                  ->nullOnDelete();

            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('status')->default('pending');
            $table->string('description')->nullable();

            // SHA-256 hash of (user_id + raw key). Unique per user.
            $table->string('idempotency_key', 64)->nullable()->index();

            $table->json('metadata')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->text('failed_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_folio_id', 'status']);
            $table->index(['destination_folio_id', 'status']);
            $table->unique('idempotency_key');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};
