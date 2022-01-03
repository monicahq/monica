<?php

namespace App\Services;

use Throwable;
use App\Jobs\ServiceQueue;
use Illuminate\Foundation\Bus\PendingDispatch;

/**
 * This trait helps dispatch a QueuableService.
 */
trait DispatchableService
{
    /**
     * Dispatch the service with the given arguments.
     *
     * @param  mixed  ...$arguments
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function dispatch(...$arguments): PendingDispatch
    {
        /** @var QueuableService $service */
        $service = new self();

        return ServiceQueue::dispatch($service, ...$arguments);
    }

    /**
     * Dispatch the service with the given arguments on the sync queue.
     *
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public static function dispatchSync(...$arguments)
    {
        /** @var QueuableService $service */
        $service = new self();

        return ServiceQueue::dispatchSync($service, ...$arguments);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     */
    public function failed(Throwable $exception): void
    {
    }
}
