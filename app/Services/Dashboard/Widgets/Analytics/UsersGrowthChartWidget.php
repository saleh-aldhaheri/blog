<?php

namespace App\Services\Dashboard\Widgets\Analytics;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UsersGrowthChartWidget
{
    /**
     * Invoke the class instance.
     */
    public function __invoke(): array
    {
        return User::select(
            DB::raw('count(*) as total'),
            DB::raw('DATE(created_at) as date')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
