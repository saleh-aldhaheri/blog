<?php

namespace App\Http\V1\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteractionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'action' => $this->action,
            'interactable_type' => $this->interactable_type,
            'interactable_id' => $this->interactable_id,
        ];
    }
}
