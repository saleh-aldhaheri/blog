<?php

namespace App\Http\V1\Controllers\Api\Admin;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Dashboard
     *
     * Returns a single aggregated payload used to populate the admin dashboard.
     * The response is split into four top-level keys:
     *
     * - **stats** — platform-wide totals (users, posts, comments, interactions).
     * - **contents** — editorial snapshots: posts per category, 10 most recent posts, and the 10 top-liked posts.
     * - **engagements** — daily interaction counts broken down by action type (like, dislike, wow, love, hate).
     * - **analytics** — daily time-series data for user and post growth, ordered chronologically.
     *
     * @group v1 /admin
     *
     * @subgroup Dashboard
     *
     * @response 200 scenario=success {
     *   "stats": {
     *     "users": 120,
     *     "posts": 340,
     *     "comments": 870,
     *     "interactions": 2100
     *   },
     *   "contents": {
     *     "post_per_category": [
     *       { "id": 1, "name": "Tech", "slug": "tech", "posts_count": 42 },
     *       { "id": 2, "name": "Life", "slug": "life", "posts_count": 18 }
     *     ],
     *     "recent_posts": [
     *       {
     *         "id": 10,
     *         "title": "Hello World",
     *         "content": {},
     *         "status": "published",
     *         "user_id": 1,
     *         "category_id": 1,
     *         "interactions_count": 5,
     *         "comments_count": 2,
     *         "created_at": "2026-01-15T12:00:00.000000Z",
     *         "updated_at": "2026-01-15T12:00:00.000000Z",
     *         "user": { "id": 1, "name": "John", "email": "john@example.com", "role": "admin" }
     *       }
     *     ],
     *     "top_liked_posts": [
     *       {
     *         "id": 5,
     *         "title": "Most Loved Post",
     *         "content": {},
     *         "status": "published",
     *         "user_id": 2,
     *         "category_id": 2,
     *         "interactions_count": 99,
     *         "comments_count": 30,
     *         "created_at": "2026-01-10T08:00:00.000000Z",
     *         "updated_at": "2026-01-10T08:00:00.000000Z",
     *         "user": { "id": 2, "name": "Jane", "email": "jane@example.com", "role": "user" }
     *       }
     *     ]
     *   },
     *   "engagements": [
     *     { "action": "like",    "date": "2026-04-28", "total": 34 },
     *     { "action": "dislike", "date": "2026-04-28", "total": 5  },
     *     { "action": "wow",     "date": "2026-04-28", "total": 12 },
     *     { "action": "love",    "date": "2026-04-28", "total": 20 },
     *     { "action": "hate",    "date": "2026-04-28", "total": 3  }
     *   ],
     *   "analytics": {
     *     "user_growth": [
     *       { "total": 10, "date": "2026-01-01" },
     *       { "total": 25, "date": "2026-01-02" }
     *     ],
     *     "post_growth": [
     *       { "total": 3, "date": "2026-01-01" },
     *       { "total": 8, "date": "2026-01-02" }
     *     ]
     *   }
     * }
     */
    public function __invoke(): JsonResponse
    {
        $data = $this->dashboardService->__invoke();

        return response()->json($data);
    }
}
