<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'label'    => $this->label,
            'currency' => $this->currency,
            'balance'  => [
                'minor'     => $this->balance_minor,
                'formatted' => $this->formatted_balance,
            ],
            'status'   => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'version'  => $this->version,
            'owner'    => $this->whenLoaded('owner', fn () => [
                'id'    => $this->owner->id,
                'name'  => $this->owner->name,
                'email' => $this->owner->email,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
