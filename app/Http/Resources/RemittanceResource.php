<?php

namespace App\Http\Resources;

use App\Http\Resources\LedgerEntryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemittanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'amount' => [
                'minor'     => $this->amount_minor,
                'formatted' => number_format($this->amount_minor / 100, 2),
                'currency'  => $this->currency,
            ],
            'description'   => $this->description,
            'status'        => $this->status->value,
            'posted_at'     => $this->posted_at?->toIso8601String(),
            'failed_reason' => $this->when(
                $this->failed_reason !== null,
                $this->failed_reason
            ),
             'source'      => $this->whenLoaded('sourceFolio', fn () =>
                $this->sourceFolio
                    ? ['id' => $this->sourceFolio->id, 'label' => $this->sourceFolio->label]
                    : ['id' => null, 'label' => 'External']
            ),
            'destination' => $this->whenLoaded('destinationFolio', fn () =>
                $this->destinationFolio
                    ? ['id' => $this->destinationFolio->id, 'label' => $this->destinationFolio->label]
                    : ['id' => null, 'label' => 'External']
            ),
            'entries'    => LedgerEntryResource::collection(
                $this->whenLoaded('ledgerEntries')
            ),
             'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
