<?php

namespace App\Http\V1\Controllers\Api\Admin;

use App\Services\Dashboard\DashboardService;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;

class DashboardController
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function __invoke(): JsonResponse
    {
        $data = $this->dashboardService->__invoke();

        return response()->json($data);
    }
}
