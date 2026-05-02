<?php

namespace App\Events;

use App\Models\Folio;
use App\Models\Remittance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FolioPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Folio      $folio,
        public readonly Remittance $remittance
    ) {}
}