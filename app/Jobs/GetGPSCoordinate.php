<?php

namespace App\Jobs;

use App\Models\Account\Place;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\RateLimited;
use App\Exceptions\RateLimitedSecondException;
use App\Services\Instance\Geolocalization\GetGPSCoordinate as GetGPSCoordinateService;

class GetGPSCoordinate implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Place
     */
    protected $place;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 10;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Place $place)
    {
        $this->place = $place->withoutRelations();
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [
            new RateLimited('GPSCoordinate'),
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (($batch = $this->batch()) !== null && $batch->cancelled()) {
            return;
        }

        try {
            app(GetGPSCoordinateService::class)->execute([
                'account_id' => $this->place->account_id,
                'place_id' => $this->place->id,
            ]);
        } catch (RateLimitedSecondException $e) {
            $this->release(15);
        }
    }
}
