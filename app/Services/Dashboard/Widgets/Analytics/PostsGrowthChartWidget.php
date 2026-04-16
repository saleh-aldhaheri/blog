<?php

namespace App\Services\Dashboard\Widgets\Analytics;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

class PostsGrowthChartWidget
{
    public function __invoke(): array
    {
        return Post::select([
            DB::raw('count(*) as total'),
            DB::raw('DATE(created_at) as date')
        ])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
