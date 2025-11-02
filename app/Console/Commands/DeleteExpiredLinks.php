<?php

namespace App\Console\Commands;

use App\Models\Links;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteExpiredLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-expired-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Esse comando deletará os links expirados do banco de dados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $deleted = Links::whereNotNull('expires_at')
            ->where('expires_at', '<', $now->subDays(30))
            ->delete();

        $this->info("{$deleted} links expirados foram removidos.");

        return Command::SUCCESS;
    }
}
