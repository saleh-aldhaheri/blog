<?php

namespace App\Http\V1\Controllers\Api\Admin;

use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\ProfileResource;
use App\Http\V1\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends BaseController
{
    public function __construct(
        private UserService $userService
    ) {}
    public function index(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);
        $search = $this->getSearch($request) ?? '';

        $users =  $this->userService->getUsers($search,  $limit);

        return UserResource::collection($users)->response();
    }

    public function show(User $user): JsonResponse
    {
        $user =  $this->userService->getUser($user);
        return new ProfileResource($user)->response();
    }


    public function destroy(User $user): Response
    {
        $this->userService->deleteUser($user);
        return response()->noContent();
    }
}
