<?php

namespace App\Http\V1\Controllers\Api\User;

use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class CommentController extends BaseController
{
    public function __construct(
        private CommentService $commentService
    ) {}

    /**
     * List comments for a post
     *
     * The `data` array holds comment objects (same fields as create/update: `id`, `content`, `post_id`, `user_id`, `user`, `interaction_counts`, `my_interaction`, `created_at`, `updated_at`). `links` and `meta` follow Laravel’s cursor paginator.
     *
     * @group v1 /user
     *
     * @subgroup Comments
     *
     * @urlParam post integer required Post ID. Example: 1
     *
     * @queryParam search string optional Search comment body. Example: great
     * @queryParam limit int optional Page size. Example: 10
     *
     * @response 200 scenario=success {
     *   "data": [
     *     {
     *       "id": 1,
     *       "content": "Nice post!",
     *       "post_id": 1,
     *       "user_id": 2,
     *       "user": { "id": 2, "name": "Jane", "email": "jane@example.com", "role": "user" },
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
    public function index(Post $post, Request $request): JsonResponse
    {
        $search = $this->getSearch($request);
        $limit = $this->getLimit($request);
        $comments = $this->commentService->getComments(
            post: $post,
            search: $search,
            limit: $limit
        );

        return CommentResource::collection($comments)
            ->response();
    }

    /**
     * Add comment
     *
     * @group v1 /user
     *
     * @subgroup Comments
     *
     * @urlParam post integer required Post ID. Example: 1
     *
     * @bodyParam comment string required Comment text (2–256 chars). Example: Nice post!
     *
     * @response 422 scenario="validation" {
     *   "message": "The comment field is required.",
     *   "errors": { "comment": ["The comment field is required."] }
     * }
     * @response 201 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "content": "Nice post!",
     *     "post_id": 1,
     *     "user_id": 2,
     *     "user": { "id": 2, "name": "Jane", "email": "jane@example.com", "role": "user" },
     *     "interaction_counts": { "like": 0, "dislike": 0, "wow": 0, "love": 0, "hate": 0 },
     *     "my_interaction": null,
     *     "created_at": "2026-01-15T12:00:00+00:00",
     *     "updated_at": "2026-01-15T12:00:00+00:00"
     *   }
     * }
     */
    public function store(Post $post, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:2', 'max:256'],
        ]);

        $comment = $this->commentService->storeComment($post, $validated['comment']);

        return new CommentResource($comment)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update comment
     *
     * @group v1 /user
     *
     * @subgroup Comments
     *
     * @urlParam comment integer required Comment ID. Example: 1
     *
     * @bodyParam comment string required New text (2–256 chars). Example: Updated text here
     *
     * @response 422 scenario="validation" {
     *   "message": "The comment must be at least 2 characters.",
     *   "errors": { "comment": ["The comment must be at least 2 characters."] }
     * }
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "content": "Updated text here",
     *     "post_id": 1,
     *     "user_id": 2,
     *     "user": { "id": 2, "name": "Jane", "email": "jane@example.com", "role": "user" },
     *     "interaction_counts": { "like": 0, "dislike": 0, "wow": 0, "love": 0, "hate": 0 },
     *     "my_interaction": null,
     *     "created_at": "2026-01-15T12:00:00+00:00",
     *     "updated_at": "2026-01-15T12:15:00+00:00"
     *   }
     * }
     * @response 403 scenario="not allowed" {
     *   "message": "This action is unauthorized."
     * }
     */
    public function update(Comment $comment, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:2', 'max:256'],
        ]);

        $comment = $this->commentService->updateComment($comment, $validated['comment']);

        return new CommentResource($comment)
            ->response();
    }

    /**
     * Delete comment
     *
     * @group v1 /user
     *
     * @subgroup Comments
     *
     * @urlParam comment integer required Comment ID. Example: 1
     *
     * @response 204 scenario=success
     * @response 403 scenario="not owner" {
     *   "message": "This action is unauthorized."
     * }
     */
    public function destroy(Comment $comment): Response
    {
        $this->commentService->delete($comment);

        return response()->noContent();
    }
}
