<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ClearNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user) {}

    /**
     * Execute the job.
     *  delete all type of the notification if they stay more then 7 days.
     */
    public function handle(): void
    {
        $this->user
            ->notifications()
            ->where('created_at', '<=', now()->subDays(7))
            ->delete();
    }
}
