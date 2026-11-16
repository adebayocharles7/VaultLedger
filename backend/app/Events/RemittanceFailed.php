<?php

namespace App\Events;

use App\Models\Remittance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemittanceFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Remittance $remittance,
        public readonly string     $reason
    ) {}
}