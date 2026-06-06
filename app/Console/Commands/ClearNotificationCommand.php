<?php

namespace App\Console\Commands;

use App\Jobs\ClearNotificationJob;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:clear-notification-command')]
#[Description('Command description')]
class ClearNotificationCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        User::whereHas('notifications', function ($query) {
            $query->where('created_at', '<=', now()->subDays(7));
        })
            ->chunk(500, function ($users) {
                $users->each(function ($user) {
                    dispatch(new ClearNotificationJob($user));
                });
            });
    }
}
