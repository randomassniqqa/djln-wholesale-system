<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanExpiredIdempotencyKeys extends Command
{
    protected $signature = 'idempotency:cleanup';

    protected $description = 'Remove expired idempotency keys older than 24 hours';

    public function handle(): int
    {
        $deleted = DB::table('idempotency_keys')
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Cleaned up {$deleted} expired idempotency keys.");
        return 0;
    }
}
