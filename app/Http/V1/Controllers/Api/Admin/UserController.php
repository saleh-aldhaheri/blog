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

    /**
     * List users
     *
     * Returns a paginated list of all registered users, optionally filtered by name or email.
     *
     * @group v1 /admin
     *
     * @subgroup Users
     *
     * @queryParam search string optional Filter users by name or email. Example: jane
     * @queryParam limit int optional Number of results per page. Example: 15
     *
     * @response 200 scenario=success {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Jane Doe",
     *       "email": "jane@example.com",
     *       "role": "user",
     *       "avatar": "https://example.com/media/1/avatar.jpg"
     *     },
     *     {
     *       "id": 2,
     *       "name": "John Admin",
     *       "email": "john@example.com",
     *       "role": "admin",
     *       "avatar": ""
     *     }
     *   ],
     *   "links": { "first": null, "last": null, "prev": null, "next": null },
     *   "meta": { "path": "https://example.com", "per_page": 15, "next_cursor": null, "prev_cursor": null }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);
        $search = $this->getSearch($request) ?? '';

        $users = $this->userService->getUsers($search, $limit);

        return UserResource::collection($users)->response();
    }

    /**
     * Get a user
     *
     * Returns the full profile of a single user by ID, including follower/following counts
     * and any extended profile fields exposed by ProfileResource.
     *
     * @group v1 /admin
     *
     * @subgroup Users
     *
     * @urlParam user integer required The ID of the user. Example: 1
     *
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "role": "user",
     *     "avatar": "https://example.com/media/1/avatar.jpg",
     *     "followers_count": 12,
     *     "following_count": 5,
     *     "posts_count": 8,
     *     "created_at": "2026-01-01T00:00:00+00:00"
     *   }
     * }
     *
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\User] 99"
     * }
     */
    public function show(User $user): JsonResponse
    {
        $user = $this->userService->getUser($user);

        return new ProfileResource($user)->response();
    }

    /**
     * Delete a user
     *
     * Permanently deletes a user account and all associated data. This action cannot be undone.
     *
     * @group v1 /admin
     *
     * @subgroup Users
     *
     * @urlParam user integer required The ID of the user to delete. Example: 1
     *
     * @response 204 scenario=success {}
     *
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\User] 99"
     * }
     */
    public function destroy(User $user): Response
    {
        $this->userService->deleteUser($user);

        return response()->noContent();
    }
}
