<?php

namespace App\Services;

use App\Data\PostData;
use App\Data\UpdatePostData;
use App\Enums\InteractionTypeEnum;
use App\Enums\PostStatusEnum;
use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostService
{
    public function getUserPosts(User $user, ?string $search = '', int $limit = 10): CursorPaginator
    {
        return $user->posts()
            ->with('category:id,name')
            ->withCount(InteractionTypeEnum::actionsInteractionsCounts())
            ->with([
                'interactions' => fn ($q) => $q->where('user_id', auth()->id()),
            ])
            ->when(
                auth()->id() !== $user->id,
                fn ($q) => $q->where('status', PostStatusEnum::PUBLISHED->value)
            )
            ->search($search)
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function getPosts(?string $search = '', int $limit = 10): CursorPaginator
    {
        return Post::query()
            ->search($search)
            ->with(['category:id,name', 'user:id,name'])
            ->withCount(InteractionTypeEnum::actionsInteractionsCounts())
            ->with([
                'interactions' => fn ($q) => $q->where('user_id', auth()->id()),
            ])
            ->where('status', PostStatusEnum::PUBLISHED->value)
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function showPost(Post $post): Post
    {
        $post->load(['category:id,name', 'user:id,name']);

        $post->loadCount(array_merge(
            ['comments'],
            InteractionTypeEnum::actionsInteractionsCounts(),
        ));

        $post->load([
            'interactions' => fn ($q) => $q->where('user_id', auth()->id()),
        ]);

        return $post;
    }

    public function storePost(PostData $postData)
    {
        $post = DB::transaction(function () use ($postData): Post {

            $post = Post::create([
                'title' => $postData->title,
                'category_id' => $postData->categoryId,
                'user_id' => auth()->id(),
                'content' => [],
                'status' => $postData->status,
            ]);

            $post->addMedia($postData->thumbnails)
                ->toMediaCollection('post-thumbnails');

            $postData
                ->content
                ->each(function ($content) use (&$post) {

                    $data = [];

                    $data['type'] = $content->type;

                    if ($content->type === 'media') {
                        $media = $post
                            ->addMedia($content->file)
                            ->toMediaCollection('post-content');
                        $data['media']['url'] = $media->getUrl();
                        $data['media']['id'] = $media->id;
                    } else {
                        $data['value'] = $content->value;
                    }

                    $data['order'] = $content->order;

                    $contentArray = $post->content ?? [];
                    $contentArray[] = $data;
                    $post->content = $contentArray;
                });

            $post->save();

            return $post;
        });

        return $post;
    }

    public function updatePost(Post $post, UpdatePostData $updatePostData): Post
    {
        return DB::transaction(function () use ($post, $updatePostData): Post {

            $post->title = $updatePostData->title;
            $post->status = $updatePostData->status;
            $post->category_id = $updatePostData->categoryId;

            if ($updatePostData->content !== null) {

                $existingMediaIds = collect($post->content)
                    ->where('type', 'media')
                    ->pluck('media.id');

                $incomingMediaIds = $updatePostData->content
                    ->toCollection()
                    ->where('type', 'media')
                    ->pluck('media.id')
                    ->filter();

                $existingMediaIds
                    ->diff($incomingMediaIds)
                    ->each(fn ($id) => Media::findOrFail($id)->delete());

                $contentArray = [];

                $updatePostData
                    ->content
                    ->toCollection()
                    ->each(function ($block) use (&$post, &$contentArray) {

                        $item = ['type' => $block->type];

                        if ($block->type === 'media') {

                            if ($block->media?->newMedia) {
                                Media::findOrFail($block->media->id)->delete();

                                $media = $post->addMedia($block->media->newMedia)
                                    ->toMediaCollection('post-content');

                                $item['media'] = [
                                    'id' => $media->id,
                                    'url' => $media->getUrl(),
                                ];
                            } else {
                                $item['media'] = [
                                    'id' => $block->media->id,
                                    'url' => $block->media->url,
                                ];
                            }
                        } else {
                            $item['value'] = $block->value;
                        }

                        $item['order'] = $block->order;

                        $contentArray[] = $item;
                    });

                $post->content = $contentArray;
            }

            $post->save();

            return $post;
        });
    }

    public function destroyPost(Post $post): void
    {
        $post->clearMediaCollection('post-thumbnails');
        $post->clearMediaCollection('post-content');
        $post->delete();
    }

    public function markAsViewed(Post $post): void
    {
        if (! auth()->user()->can('markAsViewed', $post)) {
            return; // ignore quietly
        }

        auth()->user()->viewedPosts()->syncWithoutDetaching($post->id);
    }

    public function getViewedPosts(?string $search = '', int $limit = 10): CursorPaginator
    {
        return auth()
            ->user()
            ->viewedPosts()
            ->with('category:id,name')
            ->withCount(InteractionTypeEnum::actionsInteractionsCounts())
            ->with([
                'interactions' => fn ($q) => $q->where('user_id', auth()->id()),
            ])
            ->where('status', PostStatusEnum::PUBLISHED->value)
            ->search($search)
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }
}
