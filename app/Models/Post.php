<?php

namespace App\Models;

use App\Enums\PostStatusEnum;
use App\Traits\SearchableTrait;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, InteractsWithMedia, SearchableTrait;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'category_id',
        'status',
    ];

    protected $searchable = [
        'title',
        'status',
    ];

    protected $casts = [
        'content' => 'array',
        'status' => PostStatusEnum::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('post-thumbnails')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif'])
            ->singleFile();

        $this->addMediaCollection('post-content')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif']);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->chaperone();
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'interactable');
    }

    public function viewedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'viewed_posts');
    }
}
