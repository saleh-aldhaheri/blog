<?php

namespace App\Enums;

enum InteractionTypeEnum: string
{
    case LIKE = 'like';
    case DISLIKE = 'dislike';
    case WOW = 'wow';
    case LOVE = 'love';
    case HATE = 'hate';

    public static function actionsInteractionsCounts(string $relation = 'interactions'): array
    {
        return collect(InteractionTypeEnum::cases())->mapWithKeys(function ($action) {
            return  ["interactions as {$action->value}_count" => fn($query) => $query->where('action', $action->value)];
        })->toArray();
    }
}
