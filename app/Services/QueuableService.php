<?php

namespace App\Services;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Makes a BaseService queuable using the generic ServiceQueue job.
 */
abstract class QueuableService extends BaseService implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param  array|null  $data  The data to run service.
     */
    public function __construct(
        public ?array $data = null
    ) {
        if ($data !== null) {
            $this->validateRules($data);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->execute($this->data ?? []);
    }

    /**
     * Execute the service.
     */
    abstract public function execute(array $data): void;

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        //
    }
}
