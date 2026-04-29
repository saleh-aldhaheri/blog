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

    public function index(Request $request): JsonResponse
    {
        $search = $this->getSearch($request);
        $limit = $this->getLimit($request);

        $posts = $this->postService->getPosts($search, $limit);

        return PostResource::collection($posts)
            ->response();
    }

    public function show(Post $post): JsonResponse
    {
        $post = $this->postService->showPost($post);

        return (new PostResource($post))->response();
    }

    public function update(Post $post, UpdatePostData $updatePostData): JsonResponse
    {
        $post = $this->postService->updatePost($post, $updatePostData);

        return (new PostResource($this->postWithResourcePresentation($post)))->response();
    }

    public function destroy(Post $post): Response
    {
        $this->postService->destroyPost($post);

        return response()->noContent();
    }
}
