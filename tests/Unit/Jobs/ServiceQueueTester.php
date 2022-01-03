<?php

namespace Tests\Unit\Jobs;

use Throwable;
use App\Services\BaseService;
use App\Services\QueuableService;
use App\Services\DispatchableService;

class ServiceQueueTester extends BaseService implements QueuableService
{
    use DispatchableService;

    public ?array $data = null;
    public static bool $executed = false;
    public static bool $failed = false;

    public bool $object;

    /**
     * Initialize the service.
     *
     * @param array $data
     */
    public function __construct()
    {
        self::$executed = false;
        self::$failed = false;
    }

    /**
     * Execute the service.
     */
    public function handle($data): void
    {
        $this->data = $data;
        self::$executed = true;

        if ($this->data && $this->data['throw'] === true) {
            throw new \Exception();
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     */
    public function failed(Throwable $exception): void
    {
        self::$failed = true;

        if (isset($this->obj)) {
            // variable can be touch
        }
    }
}
