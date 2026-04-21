<?php

namespace App\Http\Controllers\Api;

use App\Support\ApiResponse;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    public function __construct(
        protected ApiResponse $apiResponse
    ) {}
}
