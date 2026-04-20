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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('folio_id')->constrained('folios')->cascadeOnDelete();

            $table->foreignUlid('remittance_id')->constrained('remittances')->cascadeOnDelete();

            $table->string('type'); // 'debit' or 'credit'

            $table->unsignedBigInteger('amount_minor'); // Amount in smallest currency unit
            $table->unsignedBigInteger('running_balance_minor'); // signed: can be negative in edge cases (Folio balance after this entry)

            $table->string('narration')->nullable();

            // Ledger is append-only — no updated_at column.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['folio_id', 'created_at']);
            $table->index(['remittance_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
