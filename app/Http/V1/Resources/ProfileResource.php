<?php

namespace App\Http\V1\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'role' => $this->role,
            'posts_count' => (int) ($this->posts_count ?? 0),
            'viewed_posts_count' => (int) ($this->viewed_posts_count ?? 0),
            'followers_count' => (int) ($this->followers_count ?? 0),
            'followings_count' => (int) ($this->followings_count ?? 0),
            'avatar' => $this->getFirstMediaUrl('avatar') ?: null,

        ];

        if (auth()->id() !== $this->id) {
            $data['is_followed'] = $this->followers()->where('follower_id', auth()->id())->exists();

        }

        return $data;
    }
}
