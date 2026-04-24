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
        private PostService $postService
    ) {
        parent::__construct($apiResponse);
    }

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
     *   "message": "",
     *   "data": {
     *     "data": [{"id": 1, "title": "Hello", "status": "published"}],
     *     "path": "http://localhost/api/v1/posts",
     *     "per_page": 10
     *   }
     * }
     */
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
     *
     * @response 200 scenario=success {
     *   "message": "",
     *   "data": {
     *     "data": [],
     *     "per_page": 10
     *   }
     * }
     */
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
     *   "message": "",
     *   "data": {
     *     "data": [],
     *     "per_page": 10
     *   }
     * }
     */
    public function viewedPosts(Request $request): JsonResponse
    {
        $search = $this->getSearch($request);
        $limit = $this->getLimit($request);

        $posts = $this->postService->getViewedPosts($search, $limit);

        return $this->apiResponse->success(
            data: $posts,
            message: '',
            code: 200
        );
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
     *   "message": "",
     *   "data": {
     *     "id": 1,
     *     "title": "Hello",
     *     "status": "published",
     *     "category": {"id": 1, "name": "Tech"},
     *     "user": {"id": 1, "name": "Jane"}
     *   }
     * }
     * @response 403 scenario="forbidden (e.g. draft of another user)" {
     *   "message": "This action is unauthorized.",
     *   "errors": []
     * }
     */
    public function show(Post $post): JsonResponse
    {
        $post = $this->postService->showPost($post);

        $this->postService->markAsViewed($post);

        return $this->apiResponse->success(
            data: $post,
            message: '',
            code: 200
        );
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
     * @response 201 scenario=success {
     *   "message": "Post created successfully",
     *   "data": {
     *     "id": 5,
     *     "title": "My first long enough blog post title",
     *     "status": "published",
     *     "user_id": 1,
     *     "category_id": 1
     *   }
     * }
     */
    public function store(PostData $postData): JsonResponse
    {
        $data = $this->postService->storePost($postData);

        return $this->apiResponse->success(
            data: $data,
            message: 'Post created successfully',
            code: 201
        );
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
     * @response 200 scenario=success {
     *   "message": "Post Updated successfully",
     *   "data": {
     *     "id": 1,
     *     "title": "Updated title that is long enough here",
     *     "status": "published"
     *   }
     * }
     * @response 403 scenario="not owner" {
     *   "message": "This action is unauthorized.",
     *   "errors": []
     * }
     */
    public function update(Post $post, UpdatePostData $updatePostData): JsonResponse
    {
        $post = $this->postService->updatePost($post, $updatePostData);

        return $this->apiResponse->success(
            data: $post,
            message: 'Post Updated successfully',
            code: 200
        );
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
     *   "message": "This action is unauthorized.",
     *   "errors": []
     * }
     */
    public function destroy(Post $post): Response
    {
        $this->postService->destroyPost($post);

        return response()->noContent();
    }
}
