<?php

namespace App\Policies;

use App\Models\Interaction;
use App\Models\User;

class InteractionPolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Interaction $interaction): bool
    {
        return $interaction->user_id ===  $user->id;
    }
}
