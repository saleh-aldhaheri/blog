<?php

namespace App\Http\V1\Controllers\Api\Admin;

use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\CommentResource;
use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends BaseController
{
    public function __construct(
        private CommentService $commentService
    ) {}

    /**
     * List comments
     *
     * Returns a paginated list of all comments across every post, optionally filtered by a search term.
     * The `data` array holds comment objects (`id`, `content`, `post_id`, `user_id`, `user`,
     * `interaction_counts`, `my_interaction`, `created_at`, `updated_at`).
     * `links` and `meta` follow Laravel's cursor paginator.
     *
     * @group v1 /admin
     *
     * @subgroup Comments
     *
     * @queryParam search string optional Filter comments by content. Example: great
     * @queryParam limit int optional Number of results per page. Example: 10
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
    public function index(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);
        $search = $this->getSearch($request);

        $comments = $this->commentService->getAllComments($search, $limit);

        return CommentResource::collection($comments)->response();
    }

    /**
     * Get a comment
     *
     * Returns the full details of a single comment by its ID.
     *
     * @group v1 /admin
     *
     * @subgroup Comments
     *
     * @urlParam comment integer required The ID of the comment. Example: 1
     *
     * @response 200 scenario=success {
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
     *
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\Comment] 99"
     * }
     */
    public function show(Comment $comment): JsonResponse
    {
        return new CommentResource($this->commentService->getComment($comment))->response();
    }

    /**
     * Update a comment
     *
     * Allows an admin to edit the content of any comment regardless of ownership.
     *
     * @group v1 /admin
     *
     * @subgroup Comments
     *
     * @urlParam comment integer required The ID of the comment to update. Example: 1
     *
     * @bodyParam comment string required New comment text (2–256 chars). Example: Updated text here
     *
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
     *
     * @response 422 scenario="validation error" {
     *   "message": "The comment must be at least 2 characters.",
     *   "errors": { "comment": ["The comment must be at least 2 characters."] }
     * }
     *
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\Comment] 99"
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
     * Delete a comment
     *
     * Permanently deletes a comment. Admins can delete any comment regardless of ownership.
     * This action cannot be undone.
     *
     * @group v1 /admin
     *
     * @subgroup Comments
     *
     * @urlParam comment integer required The ID of the comment to delete. Example: 1
     *
     * @response 204 scenario=success {}
     *
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\Comment] 99"
     * }
     */
    public function destroy(Comment $comment): Response
    {
        $this->commentService->delete($comment);

        return response()->noContent();
    }
}
