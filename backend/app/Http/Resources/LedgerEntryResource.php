<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type->value,
            'amount'          => [
                'minor'     => $this->amount_minor,
                'formatted' => number_format($this->amount_minor / 100, 2),
            ],
            'running_balance' => [
                'minor'     => $this->running_balance_minor,
                'formatted' => number_format($this->running_balance_minor / 100, 2),
            ],
            'narration'       => $this->narration,
            'remittance_id'   => $this->remittance_id,
            'posted_at'       => $this->created_at->toIso8601String(),
        ];
    }
}
