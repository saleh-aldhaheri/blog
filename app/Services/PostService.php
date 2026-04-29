<?php

namespace App\Services;

use App\Data\PostData;
use App\Data\UpdatePostData;
use App\Enums\InteractionTypeEnum;
use App\Enums\PostStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostService
{
    public function getUserPosts(User $user, ?string $search = '', int $limit = 10, ?PostStatusEnum $status = null): CursorPaginator
    {
        if (auth()->id() !== $user->id) {
            $status = null;
        }

        return $user->posts()
            ->with([
                'category:id,name',
                'user:id,name,email,role',
                'user.media',
                'media',
            ])
            ->with([
                'interactions' => fn($q) => $q->where('user_id', auth()->id()),
            ])
            ->withCount('comments')
            ->withCount(InteractionTypeEnum::actionsInteractionsCounts())
            ->when(
                $status !== null,
                fn($q) => $q->where('status', $status->value)
            )
            ->when(
                auth()->id() !== $user->id,
                fn($q) => $q->where('status', PostStatusEnum::PUBLISHED->value)
            )
            ->search($search)
            ->orderBy('created_at', 'desc')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function getPosts(?string $search = '', int $limit = 10): CursorPaginator
    {
        return Post::query()
            ->search($search)
            ->with(['category:id,name', 'user:id,name,email,role', 'user.media'])
            ->withCount(InteractionTypeEnum::actionsInteractionsCounts())
            ->withCount('comments')
            ->with('media')
            ->with([
                'interactions' => fn($q) => $q->where('user_id', auth()->id()),
            ])->when(
                auth()->user()->role !== RoleEnum::ADMIN,
                fn($q) => $q->where('status', PostStatusEnum::PUBLISHED->value)
            )->orderBy('created_at', 'desc')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function showPost(Post $post): Post
    {
        $post->load([
            'category:id,name',
            'user:id,name,email,role',
            'user.media',
            'media',
        ]);

        $post->load([
            'interactions' => fn($q) => $q->where('user_id', auth()->id()),
        ]);

        $post->loadCount(array_merge(
            ['comments'],
            InteractionTypeEnum::actionsInteractionsCounts(),
        ));

        return $post;
    }

    public function storePost(PostData $postData)
    {
        return DB::transaction(function () use ($postData): Post {

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
                            ->addMedia($content->media->newMedia)
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
    }

    public function updatePost(Post $post, UpdatePostData $updatePostData): Post
    {
        return DB::transaction(function () use ($post, $updatePostData): Post {

            if (! $updatePostData->title instanceof Optional) {
                $post->title = $updatePostData->title;
            }

            if (! $updatePostData->status instanceof Optional) {
                $post->status = $updatePostData->status;
            }

            $post->category_id = $updatePostData->categoryId;

            if ($updatePostData->thumbnails) {
                $post->clearMediaCollection('post-thumbnails');
                $post->addMedia($updatePostData->thumbnails)
                    ->toMediaCollection('post-thumbnails');
            }

            if ($updatePostData->content instanceof DataCollection) {

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
                    ->each(fn($id) => Media::findOrFail($id)->delete());

                $contentArray = [];

                $updatePostData
                    ->content
                    ->toCollection()
                    ->each(function ($block) use (&$post, &$contentArray) {

                        $item = ['type' => $block->type];

                        if ($block->type === 'media') {
                            $m = $block->media;

                            if ($m->newMedia) {
                                if ($m->id) {
                                    Media::findOrFail($m->id)->delete();
                                }

                                $uploaded = $post->addMedia($m->newMedia)
                                    ->toMediaCollection('post-content');

                                $item['media'] = [
                                    'id' => $uploaded->id,
                                    'url' => $uploaded->getUrl(),
                                ];
                            } else {
                                $item['media'] = [
                                    'id' => $m->id,
                                    'url' => $m->url,
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
            ->with(['category:id,name', 'user:id,name,email,role'])
            ->withCount(InteractionTypeEnum::actionsInteractionsCounts())
            ->with([
                'interactions' => fn($q) => $q->where('user_id', auth()->id()),
            ])
            ->with('comments')
            ->where('status', PostStatusEnum::PUBLISHED->value)
            ->search($search)
            ->orderBy('created_at', 'desc')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }
}
