<?php

namespace App\Http\V1\Controllers\Api\User;

use App\Enums\BusinessExceptionsEnums;
use App\Exceptions\BusinessException;
use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\NotificationResource;
use App\Notifications\NewFollowerNotification;
use App\Notifications\PostCreatedNotification;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;

class UserController extends BaseController
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * list user notifications
     *
     * @group v1 /user
     *
     * @subgroup users
     *
     * @response 201 scenario=success
     * @response 200 scenario=success {
     *   "data": [
     *     {
     *       "id" =>  uuid,
     *       "name": "Bob",
     *       "avatar": "https://example.com/avatars/bob.jpg",
     *       "message": "Bob is following you"
     *     }
     *   ],
     *   "links": { "first": null, "last": null, "prev": null, "next": null },
     *   "meta": { "path": "https://example.com", "per_page": 10, "next_cursor": null, "prev_cursor": null }
     * }
     * @response 204 scenario=success
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);
        $notifications = $this->userService->getFollowingNotifications($limit);

        return NotificationResource::collection($notifications)->response();
    }

    /**
     * Mark a following notification as read
     *
     * @group v1 /user
     *
     * @subgroup users
     *
     * @response 204 scenario=success
     */
    public function updateFollowingNotification(DatabaseNotification $notification): Response
    {
        if ($notification->type !== NewFollowerNotification::class && $notification->type !== PostCreatedNotification::class) {
            throw new BusinessException(BusinessExceptionsEnums::INVALID, 'unable to update the notification');
        }

        $this->userService->markNotificationAsRead($notification);

        return response()->noContent();
    }
}
