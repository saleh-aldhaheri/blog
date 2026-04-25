<?php

namespace App\Http\V1\Resources;

use App\Enums\InteractionTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->whenLoaded('user')),
            'interaction_counts' => collect(InteractionTypeEnum::cases())
                ->mapWithKeys(fn (InteractionTypeEnum $action) => [
                    $action->value => (int) ($this->{"{$action->value}_count"} ?? 0),
                ])
                ->all(),
            'my_interaction' => $this->when(
                $this->relationLoaded('interactions'),
                fn () => $this->interactions->first()?->action?->value
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
