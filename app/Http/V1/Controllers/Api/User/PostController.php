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
     * Send `multipart/form-data` (not JSON). Featured image: `thumbnails`. Each `content` block must use the same numeric index in every form field, for example: `content[0][type]`, `content[0][order]`, `content[0][value]`, and for a media block `content[1][type]=media` with `content[1][order]` and `content[1][media][newMedia]` (file). The index does not have to match `order`.
     *
     * @group v1 /user
     *
     * @subgroup Posts
     *
     * @bodyParam title string required Post title (min 5). Example: My first post title
     * @bodyParam categoryId integer required Category id. Example: 1
     * @bodyParam status string optional published or draft (default draft if omitted). Example: published
     * @bodyParam thumbnails file required Featured image (jpeg, png, gif; max 10MB).
     * @bodyParam content object[] required At least one block. Each item is one block.
     * @bodyParam content[].type string required One of: heading, text, media. Example: heading
     * @bodyParam content[].order integer required Display order. Example: 1
     * @bodyParam content[].value string optional Set for heading and text blocks. Example: Introduction
     * @bodyParam content[].media object optional For type media only: send newMedia file here.
     * @bodyParam content[].media.newMedia file optional Required when type is media. In-post image (jpeg, png, gif; max 10MB). No-example
     *
     * @response 422 scenario="validation" {
     *   "message": "The title field is required. (and 2 more errors)",
     *   "errors": { "title": ["The title field is required."] }
     * }
     * @response 201 scenario=success {
     *   "data": {
     *     "id": 5,
     *     "title": "My first post title",
     *     "content": [
     *       { "type": "heading", "value": "Introduction", "order": 1 },
     *       { "type": "text", "value": "This is the body of the post.", "order": 2 },
     *       { "type": "media", "order": 3, "media": { "id": 42, "url": "https://example.com/storage/42/photo.jpg" } }
     *     ],
     *     "status": "published",
     *     "user_id": 1,
     *     "category_id": 1,
     *     "user": { "id": 1, "name": "Jane", "email": "jane@example.com", "role": "user" },
     *     "category": { "id": 1, "name": "Tech" },
     *     "thumbnail": "https://example.com/media/1/conversions/thumb.jpg",
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
     * Use `application/json` when you are not uploading files. Use `multipart/form-data` with the same indexed field names as create (`content[0][type]`, `content[0][media][newMedia]`, etc.) when replacing in-post media. Omit `content` or set it to `null` to leave body blocks unchanged. If `content` is sent (including an empty array), the stored blocks are fully replaced; media not referenced in the new list is removed.
     *
     * @group v1 /user
     *
     * @subgroup Posts
     *
     * @urlParam post integer required Post ID. Example: 1
     *
     * @bodyParam categoryId integer required Category id. Example: 2
     * @bodyParam title string optional If omitted, title is unchanged. Min 5 when present. Example: A new post title
     * @bodyParam status string optional published or draft. If omitted, status is unchanged. Example: published
     * @bodyParam thumbnails file optional New featured image; replaces the existing thumbnail when provided (jpeg, png, gif; max 10MB). No-example
     * @bodyParam content object[] optional Full list of blocks. Omit or null to keep existing. If present, replaces entire content.
     * @bodyParam content[].type string required One of: heading, text, media. Example: text
     * @bodyParam content[].order integer required Example: 1
     * @bodyParam content[].value string optional For heading and text. Example: Hello
     * @bodyParam content[].media object optional For type media: id and url from GET to keep; newMedia to replace. No-example
     * @bodyParam content[].media.id integer optional Existing media id from GET. No-example
     * @bodyParam content[].media.url string optional Existing media URL from GET. No-example
     * @bodyParam content[].media.newMedia file optional New in-post image when replacing (jpeg, png, gif; max 10MB). No-example
     *
     * @response 422 scenario="validation" {
     *   "message": "The title field must be at least 5 characters.",
     *   "errors": { "title": ["The title field must be at least 5 characters."] }
     * }
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "title": "Updated post title",
     *     "content": [
     *       { "type": "heading", "value": "Section title", "order": 1, "media": null },
     *       { "type": "text", "value": "Paragraph body.", "order": 2, "media": null },
     *       { "type": "media", "value": null, "order": 3, "media": { "id": 12, "url": "https://example.com/storage/12/image.jpg" } }
     *     ],
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
