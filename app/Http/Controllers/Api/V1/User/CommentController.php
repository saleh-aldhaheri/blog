<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Api\BaseController;
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

    public function destroy(Comment $comment): Response
    {
        $this->commentService->delete($comment);

        return response()->noContent();
    }
}
