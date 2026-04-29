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

    public function index(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);
        $search = $this->getSearch($request);

        $comments = $this->commentService->getAllComments($search, $limit);

        return CommentResource::collection($comments)->response();
    }

    public function show(Comment $comment): JsonResponse
    {
        return new CommentResource($this->commentService->getComment($comment))->response();
    }

    public function update(Comment $comment, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:2', 'max:256'],
        ]);

        $comment = $this->commentService->updateComment($comment, $validated['comment']);

        return new CommentResource($comment)
            ->response();
    }

    public function destroy(Comment $comment): Response
    {
        $this->commentService->delete($comment);

        return response()->noContent();
    }
}
