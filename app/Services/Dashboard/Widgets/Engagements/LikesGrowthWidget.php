<?php

namespace App\Services\Dashboard\Widgets\Engagements;

use App\Models\Interaction;
use Illuminate\Support\Facades\DB;

class LikesGrowthWidget
{
    /**
     * Invoke the class instance.
     */
    public function __invoke(): array
    {
        return Interaction::select([
            'action',
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        ])
            ->groupBy('action', 'date')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }
}
