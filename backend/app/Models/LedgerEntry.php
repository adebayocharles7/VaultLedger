<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A LedgerEntry is the atomic unit of double-entry bookkeeping.
 *
 * For every Remittance, exactly two LedgerEntries are written:
 *   - DEBIT  on the source Folio
 *   - CREDIT on the destination Folio
 *
 * LedgerEntries are NEVER updated or deleted once created.
 * Reversals are represented by new counter-entries.
 */
class LedgerEntry extends Model
{
    use HasFactory, HasUlids;

    // Ledger is append-only — no updates, no soft deletes.
    public $timestamps = true;
    public const UPDATED_AT = null;

    protected $fillable = [
        'folio_id',
        'remittance_id',
        'type',
        'amount_minor',
        'running_balance_minor',
        'narration',
    ];

    protected $casts = [
        'type'                   => LedgerEntryType::class,
        'amount_minor'           => 'integer',
        'running_balance_minor'  => 'integer',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    public function remittance(): BelongsTo
    {
        return $this->belongsTo(Remittance::class);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeCredits($query)
    {
        return $query->where('type', LedgerEntryType::CREDIT);
    }

    public function scopeDebits($query)
    {
        return $query->where('type', LedgerEntryType::DEBIT);
    }
}