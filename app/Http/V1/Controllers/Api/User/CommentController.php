<?php

namespace App\Http\V1\Controllers\Api\User;

use App\Http\V1\Controllers\Api\BaseController;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends BaseController
{
    public function __construct(
        ApiResponse $apiResponse,
        private CommentService $commentService
    ) {
        parent::__construct($apiResponse);
    }

    /**
     * List comments for a post
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
     *   "message": "",
     *   "data": {
     *     "data": [],
     *     "per_page": 10
     *   }
     * }
     */
    public function index(Post $post, Request $request): JsonResponse
    {
        $search = $this->getSearch($request);
        $limit = $this->getLimit($request);
        $comment = $this->commentService->getComments(
            post: $post,
            search: $search,
            limit: $limit
        );

        return $this->apiResponse->success(
            data: $comment,
            message: '',
            code: 200
        );
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
     * @response 201 scenario=success {
     *   "message": "Comment created successfully",
     *   "data": {
     *     "id": 1,
     *     "content": "Nice post!",
     *     "post_id": 1,
     *     "user_id": 2
     *   }
     * }
     */
    public function store(Post $post, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:2', 'max:256'],
        ]);

        $comment = $this->commentService->storeComment($post, $validated['comment']);

        return $this->apiResponse->success(
            data: $comment,
            message: 'Comment created successfully',
            code: 201
        );
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
     * @response 200 scenario=success {
     *   "message": "Comment updated successfully",
     *   "data": {
     *     "id": 1,
     *     "content": "Updated text here"
     *   }
     * }
     * @response 403 scenario="not allowed" {
     *   "message": "This action is unauthorized.",
     *   "errors": []
     * }
     */
    public function update(Comment $comment, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:2', 'max:256'],
        ]);

        $comment = $this->commentService->updateComment($comment, $validated['comment']);

        return $this->apiResponse->success(
            data: $comment,
            message: 'Comment updated successfully',
            code: 200
        );
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
     */
    public function destroy(Comment $comment): Response
    {
        $this->commentService->delete($comment);

        return response()->noContent();
    }
}
