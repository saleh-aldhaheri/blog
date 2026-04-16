<?php

namespace App\Http\V1\Controllers\Api\User;

use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\UserResource;
use App\Models\User;
use App\Services\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FollowController extends BaseController
{
    public function __construct(
        private FollowService $followService
    ) {}

    /**
     * List accounts I follow
     *
     * Cursor-paginated users the current user follows.
     *
     * @group v1 /user
     *
     * @subgroup Follow
     *
     * @queryParam limit int optional Page size. Example: 10
     *
     * @response 200 scenario=success {
     *   "data": [
     *     { "id": 2, "name": "Bob", "email": "bob@example.com", "role": "user" }
     *   ],
     *   "links": { "first": null, "last": null, "prev": null, "next": null },
     *   "meta": { "path": "https://example.com", "per_page": 10, "next_cursor": null, "prev_cursor": null }
     * }
     */
    public function followings(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);

        $following = $this->followService->getFollowings($limit);

        return UserResource::collection($following)
            ->response();
    }

    /**
     * List my followers
     *
     * @group v1 /user
     *
     * @subgroup Follow
     *
     * @queryParam limit int optional Page size. Example: 10
     *
     * @response 200 scenario=success {
     *   "data": [
     *     { "id": 3, "name": "Fan", "email": "fan@example.com", "role": "user" }
     *   ],
     *   "links": { "first": null, "last": null, "prev": null, "next": null },
     *   "meta": { "path": "https://example.com", "per_page": 10, "next_cursor": null, "prev_cursor": null }
     * }
     */
    public function followers(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);

        $followers = $this->followService->getFollowers($limit);

        return UserResource::collection($followers)
            ->response();
    }

    /**
     * Follow a user
     *
     * @group v1 /user
     *
     * @subgroup Follow
     *
     * @urlParam user integer required User ID to follow. Example: 2
     *
     * @response 201 scenario=success
     * @response 422 scenario="cannot follow yourself" {
     *   "message": "Cannot follow yourself",
     *   "errors": []
     * }
     */
    public function follow(User $user): Response
    {
        $this->followService->follow($user);

        return response()->noContent(201);
    }

    /**
     * Unfollow a user
     *
     * @group v1 /user
     *
     * @subgroup Follow
     *
     * @urlParam user integer required User ID to unfollow. Example: 2
     *
     * @response 204 scenario=success
     */
    public function unfollow(User $user): Response
    {
        $this->followService->unfollow($user);

        return response()->noContent();
    }
}
