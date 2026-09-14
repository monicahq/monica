<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CheckDatabaseIsReady
{
    public function handle(DiagnosingHealth $event): void
    {
        try {
            DB::connection()->select('select 1');
        } catch (Throwable $e) {
            throw new RuntimeException('The database is not answering.', previous: $e);
        }
    }
}
