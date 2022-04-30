<?php

namespace App\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use App\Services\QueuableService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ServiceQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The service to queue.
     *
     * @var QueuableService
     */
    public $service;

    /**
     * The data to run service.
     *
     * @var array
     */
    public $data;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param  QueuableService  $service
     * @param  array|null  $data
     */
    public function __construct(QueuableService $service, array $data = null)
    {
        $this->service = $service;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->service->handle($this->data);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $this->service->failed($exception);
    }
}
