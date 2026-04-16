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
        $this->ensureEmbeddedDataLoaded();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status?->value ?? $this->status,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'user' => UserResource::make($this->user),
            'category' => $this->category !== null
                ? new CategoryResource($this->category)
                : null,
            'thumbnail' => $this->getFirstMediaUrl('post-thumbnails') ?: null,
            'interaction_counts' => collect(InteractionTypeEnum::cases())
                ->mapWithKeys(fn (InteractionTypeEnum $action) => [
                    $action->value => (int) ($this->{"{$action->value}_count"} ?? 0),
                ])
                ->all(),
            'my_interaction' => $this->interactions->isNotEmpty()
                ? new InteractionResource($this->interactions->first())
                : null,
            'comments_count' => (int) ($this->comments_count ?? 0),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function ensureEmbeddedDataLoaded(): void
    {
        $post = $this->resource;

        $post->loadMissing([
            'category' => fn ($q) => $q->select('id', 'name'),
            'user' => fn ($q) => $q->select('id', 'name', 'email', 'role'),
        ]);

        if (! $post->relationLoaded('interactions')) {
            $post->load([
                'interactions' => fn ($q) => $q->where('user_id', auth()->id()),
            ]);
        }

        $hasAllInteractionCounts = collect(InteractionTypeEnum::cases())
            ->every(fn (InteractionTypeEnum $action) => array_key_exists(
                "{$action->value}_count",
                $post->getAttributes()
            ));

        if (! $hasAllInteractionCounts) {
            $post->loadCount(InteractionTypeEnum::actionsInteractionsCounts());
        }

        if (! array_key_exists('comments_count', $post->getAttributes())) {
            $post->loadCount('comments');
        }
    }
}
