<?php

namespace App\Models;

use App\Traits\SearchableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, SearchableTrait;
    protected $fillable = ['name',  'slug', 'update_at'];
    protected $searchable = ['name'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
