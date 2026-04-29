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

    public function index(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);
        $search = $this->getSearch($request) ?? '';

        $categories = $this->categoryService->getCategories($search, $limit);

        return CategoryResource::collection($categories)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255', 'unique:categories,name'],
        ]);

        $category = $this->categoryService->storeCategory($request->name);

        return new CategoryResource($category)->response()->setStatusCode(201);
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

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

    public function destroy(Category $category): Response
    {
        $this->categoryService->deleteCategory($category);

        return response()->noContent();
    }
}
