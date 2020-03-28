<?php

namespace App\Jobs\AuditLog;

use App\Services\Instance\AuditLog\LogAccountAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogAccountAudit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The audit log instance.
     *
     * @var array
     */
    public $auditLog;

    /**
     * Create a new job instance.
     *
     * @param array $auditLog
     */
    public function __construct(array $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(LogAccountAction::class)->execute(
            $this->auditLog
        );
    }
}
