<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Data\PostData;
use App\Data\UpdatePostData;
use App\Http\Controllers\Api\BaseController;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class PostController extends BaseController
{
    public function __construct(
        ApiResponse $apiResponse,
        public PostService $postService
    ) {
        parent::__construct($apiResponse);
    }

    public function index(Request $request): JsonResponse
    {
        $search = $this->getSearch($request);
        $limit = $this->getLimit($request);

        $posts = $this->postService->getPosts($search, $limit);

        return $this->apiResponse->success(
            data: $posts,
            message: '',
            code: 200
        );
    }

    public function userPosts(User $user, Request $request): JsonResponse
    {
        $search = $this->getSearch($request);
        $limit = $this->getLimit($request);

        $posts = $this->postService->getUserPosts($user, $search, $limit);

        return $this->apiResponse->success(
            data: $posts,
            message: '',
            code: 200
        );
    }

    public function show(Post $post): JsonResponse
    {
        $post = $this->postService->showPost($post);

        return $this->apiResponse->success(
            data: $post,
            message: '',
            code: 200
        );
    }

    public function store(PostData $postData): JsonResponse
    {
        $data = $this->postService->storePost($postData);

        return $this->apiResponse->success(
            data: $data,
            message: 'Post created successfully',
            code: 201
        );
    }

    public function update(Post $post, UpdatePostData $updatePostData): JsonResponse
    {
        $post = $this->postService->updatePost($post, $updatePostData);

        return $this->apiResponse->success(
            data: $post,
            message: 'Post Updated successfully',
            code: 200
        );
    }

    public function destroy(Post $post): Response
    {
        $this->postService->destroyPost($post);

        return response()->noContent();
    }
}
