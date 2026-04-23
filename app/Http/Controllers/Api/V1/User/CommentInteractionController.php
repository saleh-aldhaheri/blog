<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Enums\InteractionTypeEnum;
use App\Http\Controllers\Api\BaseController;
use App\Models\Interaction;
use App\Models\Comment;
use App\Services\InteractionService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules\Enum;

class CommentInteractionController extends BaseController
{
    public function __construct(
        ApiResponse $apiResponse,
        private InteractionService $interactionService
    ) {
        parent::__construct($apiResponse);
    }

    public function store(Comment $comment, Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', new Enum(InteractionTypeEnum::class)]
        ]);

        $interaction = $this->interactionService->storeInteraction($comment, $request->action);

        return $this->apiResponse->success(
            message: '',
            data: ['interaction' => $interaction],
            code: 201
        );
    }

    public function destroy(Comment $comment, Interaction $interaction): Response
    {
        $this->interactionService->deleteInteraction($interaction);

        return response()->noContent();
    }
}
