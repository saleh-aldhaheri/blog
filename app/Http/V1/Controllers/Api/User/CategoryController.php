<?php

namespace App\Http\V1\Controllers\Api\User;

use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseController
{
    /**
     * List categories
     *
     * Returns every category for use in post forms, filters, and similar UI. Requires a valid Sanctum token.
     *
     * @group v1 /user
     *
     * @subgroup Categories
     *
     * @response 200 scenario=success {
     *   "data": [
     *     { "id": 1, "name": "Tech" },
     *     { "id": 2, "name": "Life" }
     *   ]
     * }
     */
    public function index(): JsonResponse
    {
        return CategoryResource::collection(Category::all())
            ->response();
    }
}
