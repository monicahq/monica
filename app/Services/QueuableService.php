<?php

namespace App\Services;

use Throwable;

/**
 * Makes a BaseService queuable using the generic ServiceQueue job.
 */
interface QueuableService
{
    /**
     * Execute the service.
     *
     * @param  array  $data
     * @return void
     */
    public function handle(array $data): void;

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void;
}
