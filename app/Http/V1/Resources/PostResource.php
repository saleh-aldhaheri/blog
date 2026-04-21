<?php

namespace App\Http\V1\Resources;

use App\Enums\InteractionTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status?->value ?? $this->status,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'user' => UserResource::make($this->whenLoaded('user')),
            'category' => $this->when(
                $this->relationLoaded('category'),
                fn () => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ]
            ),
            'thumbnail' => $this->getFirstMediaUrl('post-thumbnails') ?: null,
            'interaction_counts' => collect(InteractionTypeEnum::cases())
                ->mapWithKeys(fn (InteractionTypeEnum $action) => [
                    $action->value => (int) ($this->{"{$action->value}_count"} ?? 0),
                ])
                ->all(),
            'my_interaction' => $this->when(
                $this->relationLoaded('interactions'),
                fn () => $this->interactions->first()?->action?->value
            ),
            'comments_count' => $this->when(
                array_key_exists('comments_count', $this->resource->getAttributes()),
                (int) $this->comments_count
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
