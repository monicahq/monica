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
    use Dispatchable, Queueable, InteractsWithQueue;

    /**
     * The data to run service.
     *
     * @var ?array
     */
    public ?array $data;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param  array|null  $data
     */
    public function __construct(?array $data = null)
    {
        $this->data = $data;

        $this->validateRules($data ?? []);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->execute($this->data ?? []);
    }

    /**
     * Execute the service.
     *
     * @param  array  $data
     * @return void
     */
    abstract public function execute(array $data): void;

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        //
    }
}
