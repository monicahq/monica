<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ServiceQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $service;
    protected $parameters;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $service, array $parameters)
    {
        $this->service = $service;
        $this->parameters = $parameters;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app($this->service)->execute($this->parameters);
    }
}
