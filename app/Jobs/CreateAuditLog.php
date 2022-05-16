<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The audit log instance.
     *
     * @var array
     */
    public array $auditLog;

    /**
     * Create a new job instance.
     */
    public function __construct(array $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        AuditLog::create([
            'account_id' => $this->auditLog['account_id'],
            'author_id' => $this->auditLog['author_id'],
            'author_name' => $this->auditLog['author_name'],
            'action_name' => $this->auditLog['action_name'],
            'objects' => $this->auditLog['objects'],
        ]);
    }
}
