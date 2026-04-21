<?php

namespace App\Console\Commands;

use Database\State\EnsureAdminSeeded;
use Database\State\EnsureCategoriesSeeded;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:ensure-database-seeded')]
#[Description('Command description')]
class EnsureDatabaseSeeded extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        collect([
            new EnsureAdminSeeded(),
            new EnsureCategoriesSeeded()
        ])->each->__invoke();

        $this->components->info('Database state ensured.');

        return self::SUCCESS;
    }
}
