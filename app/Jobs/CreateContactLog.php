<?php

namespace App\Jobs;

use App\Models\ContactLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateContactLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The audit log instance.
     *
     * @var array
     */
    public array $contactLog;

    /**
     * Create a new job instance.
     */
    public function __construct(array $contactLog)
    {
        $this->contactLog = $contactLog;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ContactLog::create([
            'contact_id' => $this->contactLog['contact_id'],
            'author_id' => $this->contactLog['author_id'],
            'author_name' => $this->contactLog['author_name'],
            'action_name' => $this->contactLog['action_name'],
            'objects' => $this->contactLog['objects'],
        ]);
    }
}
