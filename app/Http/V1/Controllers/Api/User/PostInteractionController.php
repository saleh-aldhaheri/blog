<?php

namespace App\Http\V1\Controllers\Api\User;

use App\Enums\InteractionTypeEnum;
use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\InteractionResource;
use App\Models\Interaction;
use App\Models\Post;
use App\Services\InteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules\Enum;

class PostInteractionController extends BaseController
{
    public function __construct(
        private InteractionService $interactionService
    ) {}

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
     * @response 422 scenario="validation" {
     *   "message": "The action field is required.",
     *   "errors": { "action": ["The action field is required."] }
     * }
     * @response 201 scenario=success {
     *   "data": {
     *       "id": 2,
     *       "action": "like",
     *       "user_id": 1,
     *       "interactable_type": "App\\Models\\Post",
     *       "interactable_id": 1
     *   }
     * }
     */
    public function store(Post $post, Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', new Enum(InteractionTypeEnum::class)],
        ]);

        $interaction = $this->interactionService->storeInteraction($post, $request->action);

        return new InteractionResource($interaction)->response()->setStatusCode(201);
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
     * @response 403 scenario="not allowed" {
     *   "message": "This action is unauthorized."
     * }
     */
    public function destroy(Post $post, Interaction $interaction): Response
    {
        $this->interactionService->deleteInteraction($interaction);

        return response()->noContent();
    }
}
