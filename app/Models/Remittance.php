<?php

namespace App\Models;

use App\Enums\RemittanceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Remittance represents an intent to move funds between two Folios.
 *
 * It stays in PENDING status until the PostingService commits it.
 * Once POSTED, LedgerEntries exist and the balance has moved.
 * REVERSED status means a matching counter-remittance has been posted.
 */
class Remittance extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'source_folio_id',
        'destination_folio_id',
        'amount_minor',
        'currency',
        'status',
        'description',
        'idempotency_key',
        'metadata',
        'posted_at',
        'failed_reason',
    ];

    protected $casts = [
        'status'       => RemittanceStatus::class,
        'amount_minor' => 'integer',
        'metadata'     => 'array',
        'posted_at'    => 'datetime',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function sourceFolio(): BelongsTo
    {
        return $this->belongsTo(Folio::class, 'source_folio_id');
    }

    public function destinationFolio(): BelongsTo
    {
        return $this->belongsTo(Folio::class, 'destination_folio_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopePosted($query)
    {
        return $query->where('status', RemittanceStatus::POSTED);
    }

    public function scopePending($query)
    {
        return $query->where('status', RemittanceStatus::PENDING);
    }
}