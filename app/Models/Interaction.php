<?php

namespace App\Models;

use App\Enums\InteractionTypeEnum;
use Database\Factories\InteractionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Interaction extends Model
{
    /** @use HasFactory<InteractionFactory> */
    use HasFactory;

    protected $fillable = [
        'action',
        'user_id',
        'interactable_type',
        'interactable_id',
    ];

    protected $casts = [
        'action' => InteractionTypeEnum::class,
    ];

    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }
}
