<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Services\FollowService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends BaseController
{
    public function __construct(
        ApiResponse $apiResponse,
        private FollowService $followService
    ) {
        parent::__construct($apiResponse);
    }

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
     *   "message": "",
     *   "data": {
     *     "data": [{"id": 2, "name": "Bob"}],
     *     "per_page": 10
     *   }
     * }
     */
    public function followings(Request $request)
    {
        $limit = $this->getLimit($request);

        $following = $this->followService->getFollowings($limit);

        return $this->apiResponse->success(
            data: $following,
            message: '',
            code: 200
        );
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
     *   "message": "",
     *   "data": {
     *     "data": [],
     *     "per_page": 10
     *   }
     * }
     */
    public function followers(Request $request)
    {
        $limit = $this->getLimit($request);

        $followers = $this->followService->getFollowers($limit);

        return $this->apiResponse->success(
            data: $followers,
            message: '',
            code: 200
        );
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
     * @response 201 scenario=success {
     *   "message": "",
     *   "data": ""
     * }
     * @response 422 scenario="cannot follow yourself" {
     *   "message": "Cannot follow yourself",
     *   "errors": []
     * }
     */
    public function follow(User $user): JsonResponse
    {
        $this->followService->follow($user);

        return $this->apiResponse->success(
            data: '',
            message: '',
            code: 201
        );
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
     * @response 200 scenario=success {
     *   "message": "",
     *   "data": ""
     * }
     */
    public function unfollow(User $user): JsonResponse
    {
        $this->followService->unfollow($user);

        return $this->apiResponse->success(
            data: '',
            message: '',
            code: 200
        );
    }
}
