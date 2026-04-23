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

    public function follow(User $user): JsonResponse
    {
        $this->followService->follow($user);

        return $this->apiResponse->success(
            data: '',
            message: '',
            code: 201
        );
    }

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
