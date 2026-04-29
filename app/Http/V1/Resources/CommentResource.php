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
        $this->ensureEmbeddedDataLoaded();

        return [
            'id' => $this->id,
            'content' => $this->content,
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->user),
            'interaction_counts' => collect(InteractionTypeEnum::cases())
                ->mapWithKeys(fn(InteractionTypeEnum $action) => [
                    $action->value => (int) ($this->{"{$action->value}_count"} ?? 0),
                ])
                ->all(),
            'my_interaction' => $this->interactions->isNotEmpty()
                ? new InteractionResource($this->interactions->first())
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function ensureEmbeddedDataLoaded(): void
    {
        $comment = $this->resource;

        $comment->loadMissing([
            'user' => fn($q) => $q->select('id', 'name', 'email', 'role'),
        ]);

        if (! $comment->relationLoaded('interactions')) {
            $comment->load([
                'interactions' => fn($q) => $q->where('user_id', auth()->id()),
            ]);
        }

        $hasAllInteractionCounts = collect(InteractionTypeEnum::cases())
            ->every(fn(InteractionTypeEnum $action) => array_key_exists(
                "{$action->value}_count",
                $comment->getAttributes()
            ));

        if (! $hasAllInteractionCounts) {
            $comment->loadCount(InteractionTypeEnum::actionsInteractionsCounts());
        }
    }
}
