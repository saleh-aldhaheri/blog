<?php

namespace App\Services\Dashboard;

use App\Services\Dashboard\Widgets\Analytics\PostsGrowthChartWidget;
use App\Services\Dashboard\Widgets\Analytics\UsersGrowthChartWidget;
use App\Services\Dashboard\Widgets\Contents\PostsPerCategoryWidget;
use App\Services\Dashboard\Widgets\Contents\RecentPostsWidget;
use App\Services\Dashboard\Widgets\Contents\TopLikedPostsWidget;
use App\Services\Dashboard\Widgets\Engagements\LikesGrowthWidget;
use App\Services\Dashboard\Widgets\Overview\StateWidget;

class DashboardService
{
    public function __invoke()
    {
        return  [
            'stats' => new StateWidget()->__invoke(),
            'contents' => [
                'post_per_category' =>  new PostsPerCategoryWidget()->__invoke(),
                'recent_posts' => new RecentPostsWidget()->__invoke(),
                'top_liked_posts' => new TopLikedPostsWidget()->__invoke()
            ],
            'engagements' => new LikesGrowthWidget()->__invoke(),
            'analytics' => [
                'user_growth' =>  new UsersGrowthChartWidget()->__invoke(),
                'post_growth' => new PostsGrowthChartWidget()->__invoke()
            ],
        ];
    }
}
