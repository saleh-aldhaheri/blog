<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Notifications\PostCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class DistributePostCreatedNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $author, public Post $post) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->author->followers()->chunk(200, function ($followers) {
            Notification::send($followers, new PostCreatedNotification($this->author, $this->post));
        });
    }
}
