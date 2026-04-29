<?php

namespace App\Http\V1\Controllers\Api\Admin;

use App\Data\UpdatePostData;
use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class PostController extends BaseController
{
    public function __construct(
        private PostService $postService
    ) {}

    /**
     * List posts
     *
     * Returns a cursor-paginated list of all posts across all users and statuses (published and draft).
     *
     * @group v1 /admin
     *
     * @subgroup Posts
     *
     * @queryParam search string optional Filter by title. Example: laravel
     * @queryParam limit int optional Page size (1–50, default 10). Example: 10
     *
     * @response 200 scenario=success {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Hello World",
     *       "content": [],
     *       "status": "published",
     *       "user_id": 1,
     *       "category_id": 1,
     *       "user": { "id": 1, "name": "Jane", "email": "jane@example.com", "role": "user" },
     *       "category": { "id": 1, "name": "Tech" },
     *       "thumbnail": "https://example.com/media/1/thumb.jpg",
     *       "interaction_counts": { "like": 0, "dislike": 0, "wow": 0, "love": 0, "hate": 0 },
     *       "my_interaction": null,
     *       "comments_count": 3,
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
     * Get a post
     *
     * Returns a single post by ID. Admins can view any post regardless of status (published or draft).
     *
     * @group v1 /admin
     *
     * @subgroup Posts
     *
     * @urlParam post integer required The ID of the post. Example: 1
     *
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "title": "Hello World",
     *     "content": [
     *       { "type": "heading", "value": "Introduction", "order": 1, "media": null },
     *       { "type": "text",    "value": "Body paragraph.", "order": 2, "media": null },
     *       { "type": "media",   "value": null, "order": 3, "media": { "id": 42, "url": "https://example.com/storage/42/photo.jpg" } }
     *     ],
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
     *
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\Post] 99"
     * }
     */
    public function show(Post $post): JsonResponse
    {
        $post = $this->postService->showPost($post);

        return (new PostResource($post))->response();
    }

    /**
     * Update a post
     *
     * Allows an admin to edit any post regardless of ownership. Use `application/json` when not
     * uploading files. Use `multipart/form-data` with indexed field names (`content[0][type]`,
     * `content[0][media][newMedia]`, etc.) when replacing in-post media. Omit `content` or set
     * it to `null` to leave body blocks unchanged. If `content` is sent (including an empty array),
     * the stored blocks are fully replaced and any media not referenced in the new list is removed.
     *
     * @group v1 /admin
     *
     * @subgroup Posts
     *
     * @urlParam post integer required The ID of the post to update. Example: 1
     *
     * @bodyParam categoryId integer required Category ID. Example: 2
     * @bodyParam title string optional Post title (min 5). If omitted, the title is unchanged. Example: An updated post title
     * @bodyParam status string optional published or draft. If omitted, the status is unchanged. Example: published
     * @bodyParam thumbnails file optional New featured image; replaces the existing thumbnail when provided (jpeg, png, gif; max 10 MB). No-example
     * @bodyParam content object[] optional Full list of content blocks. Omit or null to keep existing. If present, fully replaces all stored blocks.
     * @bodyParam content[].type string required One of: heading, text, media. Example: text
     * @bodyParam content[].order integer required Display order. Example: 1
     * @bodyParam content[].value string optional Required for heading and text blocks. Example: Hello
     * @bodyParam content[].media object optional For type media only: pass id and url to keep an existing image, or newMedia to replace it. No-example
     * @bodyParam content[].media.id integer optional Existing media ID (from GET). No-example
     * @bodyParam content[].media.url string optional Existing media URL (from GET). No-example
     * @bodyParam content[].media.newMedia file optional New in-post image (jpeg, png, gif; max 10 MB). No-example
     *
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "title": "An updated post title",
     *     "content": [
     *       { "type": "heading", "value": "Section title", "order": 1, "media": null },
     *       { "type": "text",    "value": "Paragraph body.", "order": 2, "media": null },
     *       { "type": "media",   "value": null, "order": 3, "media": { "id": 12, "url": "https://example.com/storage/12/image.jpg" } }
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
     *     "updated_at": "2026-01-15T12:15:00+00:00"
     *   }
     * }
     *
     * @response 422 scenario="validation error" {
     *   "message": "The title field must be at least 5 characters.",
     *   "errors": { "title": ["The title field must be at least 5 characters."] }
     * }
     *
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\Post] 99"
     * }
     */
    public function update(Post $post, UpdatePostData $updatePostData): JsonResponse
    {
        $post = $this->postService->updatePost($post, $updatePostData);

        return (new PostResource($this->postWithResourcePresentation($post)))->response();
    }

    /**
     * Delete a post
     *
     * Permanently deletes a post, its media, and all associated content. Admins can delete any
     * post regardless of ownership. This action cannot be undone.
     *
     * @group v1 /admin
     *
     * @subgroup Posts
     *
     * @urlParam post integer required The ID of the post to delete. Example: 1
     *
     * @response 204 scenario=success {}
     *
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\Post] 99"
     * }
     */
    public function destroy(Post $post): Response
    {
        $this->postService->destroyPost($post);

        return response()->noContent();
    }
}
