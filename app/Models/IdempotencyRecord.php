<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores hashed idempotency keys so duplicate requests
 * return a cached response rather than re-executing.
 *
 * Keyed on: SHA-256(user_id + raw_key)
 */
class IdempotencyRecord extends Model
{
    use HasUlids;

    protected $fillable = [
        'key_hash',
        'user_id',
        'response_status',
        'response_body',
        'resolved_at',
        'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'resolved_at'   => 'datetime',
        'expires_at'    => 'datetime',
    ];

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}