<?php

namespace App\Http\Controllers\Api;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    public function __construct(
        protected ApiResponse $apiResponse
    ) {}

    public function getLimit(Request $request): int
    {
        $limit = $request->query('limit');

        if ($limit === null || $limit === '') {
            return 10;
        }

        $limit = (int) $limit;

        if ($limit < 1) {
            return 10;
        }

        return $limit > 50 ? 10 : $limit;
    }

    protected function getSearch(Request $request): ?string
    {
        $search = $request->query('search');

        if (! is_string($search)) {
            return null;
        }

        $search = trim($search);

        return $search === '' ? null : $search;
    }
}
