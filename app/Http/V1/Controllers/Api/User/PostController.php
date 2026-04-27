<?php

namespace App\Http\V1\Controllers\Api\User;

use App\Data\PostData;
use App\Data\UpdatePostData;
use App\Enums\InteractionTypeEnum;
use App\Enums\PostStatusEnum;
use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;

class PostController extends BaseController
{
    public function __construct(
        private PostService $postService
    ) {}

    /**
     * List published posts
     *
     * Cursor-paginated feed of published posts.
     *
     * @group v1 /user
     *
     * @subgroup Posts
     *
     * @queryParam search string optional Filter by title (search). Example: laravel
     * @queryParam limit int optional Page size (1–50, default 10). Example: 10
     *
     * @response 200 scenario=success {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Hello",
     *       "status": "published",
     *       "user": { "id": 1, "name": "Jane", "email": "jane@example.com", "role": "user" },
     *       "category": { "id": 1, "name": "Tech" },
     *       "thumbnail": "https://example.com/media/1/thumb.jpg",
     *       "interaction_counts": { "like": 0, "dislike": 0, "wow": 0, "love": 0, "hate": 0 },
     *       "my_interaction": null,
     *       "created_at": "2026-01-15T12:00:00+00:00",
     *       "updated_at": "2026-01-15T12:00:00+00:00"
     *     }
     *   ],
     *   "links": { "first": null, "last": null, "prev": null, "next": null },
     *   "meta": { "path": "https://example.com", "per_page": 10, "next_cursor": null, "prev_cursor": null }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $search = $this->getSearch($request);
        $limit = $this->getLimit($request);

        $posts = $this->postService->getPosts($search, $limit);

        return PostResource::collection($posts)
            ->response();
    }

    /**
     * List a user's posts
     *
     * When the authenticated user is the owner, drafts are included. Otherwise only published posts are returned.
     *
     * @group v1 /user
     *
     * @subgroup Posts
     *
     * @urlParam user integer required The user ID whose posts to list. Example: 1
     *
     * @queryParam search string optional Filter by title. Example: tips
     * @queryParam limit int optional Page size (1–50, default 10). Example: 15
     * @queryParam status string optional When the authenticated user is the profile owner, filter by `published` or `draft`. Ignored for other viewers. Example: draft
     *
     * @response 200 scenario=success {
     *   "data": [
     *      *     {
     *       "id": 1,
     *       "title": "Hello",
     *       "status": "published",
     *       "user": { "id": 1, "name": "Jane", "email": "jane@example.com", "role": "user" },
     *       "category": { "id": 1, "name": "Tech" },
     *       "thumbnail": "https://example.com/media/1/thumb.jpg",
     *       "interaction_counts": { "like": 0, "dislike": 0, "wow": 0, "love": 0, "hate": 0 },
     *       "my_interaction": null,
     *       "created_at": "2026-01-15T12:00:00+00:00",
     *       "updated_at": "2026-01-15T12:00:00+00:00"
     *     }
     * ],
     *   "links": { "first": null, "last": null, "prev": null, "next": null },
     *   "meta": { "path": "https://example.com", "per_page": 10, "next_cursor": null, "prev_cursor": null }
     * }
     */
    public function userPosts(User $user, Request $request): JsonResponse
    {
        $search = $this->getSearch($request);
        $limit = $this->getLimit($request);

        $status = null;
        if (auth()->id() === $user->id && $request->filled('status')) {
            $validated = $request->validate([
                'status' => ['required', 'string', Rule::enum(PostStatusEnum::class)],
            ]);
            $status = $validated['status'] instanceof PostStatusEnum
                ? $validated['status']
                : PostStatusEnum::from($validated['status']);
        }

        $posts = $this->postService->getUserPosts($user, $search, $limit, $status);

        return PostResource::collection($posts)
            ->response();
    }

    /**
     * List viewed posts
     *
     * Published posts the current user has viewed (cursor pagination).
     *
     * @group v1 /user
     *
     * @subgroup Posts
     *
     * @queryParam search string optional Filter by title. Example: php
     * @queryParam limit int optional Page size. Example: 10
     *
     * @response 200 scenario=success {
     *   "data": [
     *      *     {
     *       "id": 1,
     *       "title": "Hello",
     *       "status": "published",
     *       "user": { "id": 1, "name": "Jane", "email": "jane@example.com", "role": "user" },
     *       "category": { "id": 1, "name": "Tech" },
     *       "thumbnail": "https://example.com/media/1/thumb.jpg",
     *       "interaction_counts": { "like": 0, "dislike": 0, "wow": 0, "love": 0, "hate": 0 },
     *       "my_interaction": null,
     *       "created_at": "2026-01-15T12:00:00+00:00",
     *       "updated_at": "2026-01-15T12:00:00+00:00"
     *     }
     * ],
     *   "links": { "first": null, "last": null, "prev": null, "next": null },
     *   "meta": { "path": "https://example.com", "per_page": 10, "next_cursor": null, "prev_cursor": null }
     * }
     */
    public function viewedPosts(Request $request): JsonResponse
    {
        $search = $this->getSearch($request);
        $limit = $this->getLimit($request);

        $posts = $this->postService->getViewedPosts($search, $limit);

        return PostResource::collection($posts)
            ->response();
    }

    /**
     * Show post
     *
     * Loads the post (policy: draft only for owner/admin). Marks the post as viewed for the current user when allowed.
     *
     * @group v1 /user
     *
     * @subgroup Posts
     *
     * @urlParam post integer required Post ID. Example: 1
     *
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "title": "Hello",
     *     "content": [],
     *     "status": "published",
     *     "user_id": 1,
     *     "category_id": 1,
     *     "user": { "id": 1, "name": "Jane", "email": "jane@example.com", "role": "user" },
     *     "category": { "id": 1, "name": "Tech" },
     *     "thumbnail": "https://example.com/media/1/thumb.jpg",
     *     "interaction_counts": { "like": 0, "dislike": 0, "wow": 0, "love": 0, "hate": 0 },
     *     "my_interaction": null,
     *     "comments_count": 3,
     *     "created_at": "2026-01-15T12:00:00+00:00",
     *     "updated_at": "2026-01-15T12:00:00+00:00"
     *   }
     * }
     * @response 403 scenario="forbidden (e.g. draft of another user)" {
     *   "message": "This action is unauthorized."
     * }
     */
    public function show(Post $post): JsonResponse
    {
        $post = $this->postService->showPost($post);

        $this->postService->markAsViewed($post);

        return (new PostResource($post))->response();
    }

    /**
     * Create post
     *
     * `multipart/form-data` request.
     *
     * @group v1 /user
     *
     * @subgroup Posts
     *
     * @bodyParam title string required Post title (min 20 chars). Example: My first long enough blog post title
     * @bodyParam categoryId integer required Category ID. Example: 1
     * @bodyParam status string optional `published` or `draft`. Example: published
     * @bodyParam thumbnails file required Featured image.
     * @bodyParam content array required Array of blocks (`heading`, `text`, `media`). No-example
     *
     * @response 422 scenario="validation" {
     *   "message": "The title field is required. (and 2 more errors)",
     *   "errors": { "title": ["The title field is required."] }
     * }
     * @response 201 scenario=success {
     *   "data": {
     *     "id": 5,
     *     "title": "My first long enough blog post title",
     *     "content": [],
     *     "status": "published",
     *     "user_id": 1,
     *     "category_id": 1,
     *     "user": { "id": 1, "name": "Jane", "email": "jane@example.com", "role": "user" },
     *     "category": { "id": 1, "name": "Tech" },
     *     "thumbnail": "https://example.com/media/1/thumb.jpg",
     *     "interaction_counts": { "like": 0, "dislike": 0, "wow": 0, "love": 0, "hate": 0 },
     *     "my_interaction": null,
     *     "comments_count": 0,
     *     "created_at": "2026-01-15T12:00:00+00:00",
     *     "updated_at": "2026-01-15T12:00:00+00:00"
     *   }
     * }
     */
    public function store(PostData $postData): JsonResponse
    {
        $data = $this->postService->storePost($postData);

        return (new PostResource($this->postWithResourcePresentation($data)))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update post
     *
     * `multipart/form-data`request.
     *
     * @group v1 /user
     *
     * @subgroup Posts
     *
     * @urlParam post integer required Post ID. Example: 1
     *
     * @bodyParam title string optional Min 20 when present.
     * @bodyParam categoryId integer required
     * @bodyParam status string optional `published` or `draft`
     * @bodyParam content array optional Replacement blocks; omit or null to leave content unchanged. No-example
     *
     * @response 422 scenario="validation" {
     *   "message": "The title field must be at least 20 characters.",
     *   "errors": { "title": ["The title field must be at least 20 characters."] }
     * }
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "title": "Updated title that is long enough here",
     *     "content": [],
     *     "status": "published",
     *     "user_id": 1,
     *     "category_id": 2,
     *     "user": { "id": 1, "name": "Jane", "email": "jane@example.com", "role": "user" },
     *     "category": { "id": 2, "name": "Life" },
     *     "thumbnail": "https://example.com/media/1/thumb.jpg",
     *     "interaction_counts": { "like": 0, "dislike": 0, "wow": 0, "love": 0, "hate": 0 },
     *     "my_interaction": null,
     *     "comments_count": 0,
     *     "created_at": "2026-01-15T12:00:00+00:00",
     *     "updated_at": "2026-01-15T12:00:00+00:00"
     *   }
     * }
     * @response 403 scenario="not owner" {
     *   "message": "This action is unauthorized."
     * }
     */
    public function update(Post $post, UpdatePostData $updatePostData): JsonResponse
    {
        $post = $this->postService->updatePost($post, $updatePostData);

        return (new PostResource($this->postWithResourcePresentation($post)))->response();
    }

    /**
     * Delete post
     *
     * Requires `delete` policy. Removes media and the post record.
     *
     * @group v1 /user
     *
     * @subgroup Posts
     *
     * @urlParam post integer required Post ID. Example: 1
     *
     * @response 204 scenario=success
     * @response 403 scenario="not owner" {
     *   "message": "This action is unauthorized."
     * }
     */
    public function destroy(Post $post): Response
    {
        $this->postService->destroyPost($post);

        return response()->noContent();
    }

    private function postWithResourcePresentation(Post $post): Post
    {
        $post->load(['category:id,name', 'user:id,name,email,role']);
        $post->loadCount(array_merge(
            ['comments'],
            InteractionTypeEnum::actionsInteractionsCounts(),
        ));
        $post->load([
            'interactions' => fn ($q) => $q->where('user_id', auth()->id()),
        ]);

        return $post;
    }
}
