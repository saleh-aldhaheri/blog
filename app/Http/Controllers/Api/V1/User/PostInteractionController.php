<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Enums\InteractionTypeEnum;
use App\Http\Controllers\Api\BaseController;
use App\Models\Interaction;
use App\Models\Post;
use App\Services\InteractionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules\Enum;

class PostInteractionController extends BaseController
{
    public function __construct(
        ApiResponse $apiResponse,
        private InteractionService $interactionService
    ) {
        parent::__construct($apiResponse);
    }

    /**
     * Add interaction on post
     *
     * @group v1 /user
     *
     * @subgroup Interactions
     *
     * @urlParam post integer required Post ID. Example: 1
     *
     * @bodyParam action string required One of: `like`, `dislike`, `wow`, `love`, `hate`. Example: like
     *
     * @response 201 scenario=success {
     *   "message": "",
     *   "data": {
     *     "interaction": {
     *       "id": 1,
     *       "action": "like",
     *       "user_id": 1,
     *       "interactable_type": "App\\Models\\Post",
     *       "interactable_id": 1
     *     }
     *   }
     * }
     */
    public function store(Post $post, Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', new Enum(InteractionTypeEnum::class)],
        ]);

        $interaction = $this->interactionService->storeInteraction($post, $request->action);

        return $this->apiResponse->success(
            message: '',
            data: ['interaction' => $interaction],
            code: 201
        );
    }

    /**
     * Remove interaction on post
     *
     * @group v1 /user
     *
     * @subgroup Interactions
     *
     * @urlParam post integer required Post ID. Example: 1
     * @urlParam interaction integer required Interaction ID. Example: 1
     *
     * @response 204 scenario=success
     */
    public function destroy(Post $post, Interaction $interaction): Response
    {
        $this->interactionService->deleteInteraction($interaction);

        return response()->noContent();
    }
}
