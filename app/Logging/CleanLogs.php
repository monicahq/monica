<?php

namespace App\Logging;

use App\Interfaces\ServiceInterface;
use App\Models\Log;
use App\Services\QueuableService;
use Carbon\Carbon;

class CleanLogs extends QueuableService implements ServiceInterface
{
    /**
     * Clean logs list.
     */
    public function execute(array $data): void
    {
        Log::where('created_at', '<', Carbon::now()->subDays(15))->delete();
    }
}
