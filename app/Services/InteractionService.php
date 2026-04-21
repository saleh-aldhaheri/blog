<?php

namespace App\Services;

use App\Models\Interaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class InteractionService
{
    public function storeInteraction(Model $model, string $action): Interaction
    {
        $interaction = Interaction::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'interactable_type' => get_class($model),
                'interactable_id' => $model->id,
            ],
            [
                'action' => $action,
            ]
        );

        return $interaction;
    }

    public function deleteInteraction(Interaction $interaction): void
    {
        $interaction->delete();
    }
}
