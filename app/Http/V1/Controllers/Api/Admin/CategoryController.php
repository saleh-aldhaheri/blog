<?php

namespace App\Http\V1\Controllers\Api\Admin;

use App\Http\V1\Controllers\Api\BaseController;
use App\Http\V1\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CategoryController extends BaseController
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    /**
     * List categories
     *
     * Returns a paginated list of categories, optionally filtered by a search term.
     *
     * @group v1 /admin
     *
     * @subgroup Categories
     *
     * @queryParam search string optional Filter categories by name. Example: Tech
     * @queryParam limit int optional Number of results per page. Example: 15
     *
     * @response 200 scenario=success {
     *   "data": [
     *     { "id": 1, "name": "Tech" },
     *     { "id": 2, "name": "Life" }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);
        $search = $this->getSearch($request) ?? '';

        $categories = $this->categoryService->getCategories($search, $limit);

        return CategoryResource::collection($categories)->response();
    }

    /**
     * Create a category
     *
     * Creates a new category. The name must be unique across all categories.
     *
     * @group v1 /admin
     *
     * @subgroup Categories
     *
     * @bodyParam name string required The category name. Must be between 2 and 255 characters and unique. Example: Technology
     *
     * @response 201 scenario=success {
     *   "data": {
     *     "id": 3,
     *     "name": "Technology"
     *   }
     * }
     * @response 422 scenario="validation error" {
     *   "message": "The name has already been taken.",
     *   "errors": {
     *     "name": ["The name has already been taken."]
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255', 'unique:categories,name'],
        ]);

        $category = $this->categoryService->storeCategory($request->name);

        return new CategoryResource($category)->response()->setStatusCode(201);
    }

    /**
     * Get a category
     *
     * Returns the details of a single category by its ID.
     *
     * @group v1 /admin
     *
     * @subgroup Categories
     *
     * @urlParam category int required The ID of the category. Example: 1
     *
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "name": "Tech"
     *   }
     * }
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\Category] 99"
     * }
     */
    public function show(Category $category): JsonResponse
    {
        return new CategoryResource($category)->response();
    }

    /**
     * Update a category
     *
     * Updates the name of an existing category. The new name must be unique, ignoring the current category.
     *
     * @group v1 /admin
     *
     * @subgroup Categories
     *
     * @urlParam category int required The ID of the category to update. Example: 1
     *
     * @bodyParam name string required The new category name. Must be between 2 and 255 characters and unique. Example: Science
     *
     * @response 200 scenario=success {
     *   "data": {
     *     "id": 1,
     *     "name": "Science"
     *   }
     * }
     * @response 422 scenario="validation error" {
     *   "message": "The name has already been taken.",
     *   "errors": {
     *     "name": ["The name has already been taken."]
     *   }
     * }
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\Category] 99"
     * }
     */
    public function update(Category $category, Request $request): JsonResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
        ]);

        $category = $this->categoryService->updateCategory($category, $request->name);

        return new CategoryResource($category)->response();
    }

    /**
     * Delete a category
     *
     * Permanently deletes a category. This action cannot be undone.
     *
     * @group v1 /admin
     *
     * @subgroup Categories
     *
     * @urlParam category int required The ID of the category to delete. Example: 1
     *
     * @response 204 scenario=success {}
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\Category] 99"
     * }
     */
    public function destroy(Category $category): Response
    {
        $this->categoryService->deleteCategory($category);

        return response()->noContent();
    }
}
