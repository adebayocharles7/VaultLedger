<?php

namespace App\Models;

use App\Enums\FolioStatus;
use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Folio is the domain term for a wallet.
 *
 * Balance is stored as an integer in the smallest currency unit
 * (e.g. kobo for NGN, cents for USD) to avoid floating-point drift.
 *
 * @property string       $id
 * @property string       $user_id
 * @property string       $currency
 * @property int          $balance_minor     Balance in smallest unit (e.g. kobo)
 * @property int          $version           Optimistic-lock counter
 * @property FolioStatus  $status
 */
class Folio extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'currency',
        'balance_minor',
        'version',
        'status',
        'label',
    ];

    protected $casts = [
        'status'        => FolioStatus::class,
        'balance_minor' => 'integer',
        'version'       => 'integer',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function sentRemittances(): HasMany
    {
        return $this->hasMany(Remittance::class, 'source_folio_id');
    }

    public function receivedRemittances(): HasMany
    {
        return $this->hasMany(Remittance::class, 'destination_folio_id');
    }

    // ──────────────────────────────────────────────
    // Computed helpers
    // ──────────────────────────────────────────────

    /**
     * Returns the balance as a human-readable decimal string.
     * e.g. balance_minor=150000 (kobo) => "1500.00"
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance_minor / 100, 2);
    }

    /**
     * Verify that the stored balance matches the ledger.
     * Returns the discrepancy (0 = clean).
     */
    public function computeLedgerBalance(): int
    {
        $credits = $this->ledgerEntries()
            ->where('type', LedgerEntryType::CREDIT)
            ->sum('amount_minor');

        $debits = $this->ledgerEntries()
            ->where('type', LedgerEntryType::DEBIT)
            ->sum('amount_minor');

        return (int) ($credits - $debits);
    }

    public function isOperational(): bool
    {
        return $this->status->canTransact();
    }
}