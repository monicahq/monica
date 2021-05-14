<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\Account\Place;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\RateLimited;
use App\Services\Instance\Geolocalization\GetGPSCoordinate as GetGPSCoordinateService;


class GetGPSCoordinate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Place
     */
    protected $place;

    /**
     * @var GuzzleClient
     */
    protected $client;

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
    public function __construct(Place $place, GuzzleClient $client = null)
    {
        $this->place = $place->withoutRelations();
        $this->client = $client;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [
            new RateLimited('GPSCoordinatePerMinute'),
            new RateLimited('GPSCoordinatePerDay'),
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(GetGPSCoordinateService::class)->execute([
            'account_id' => $this->place->account_id,
            'place_id' => $this->place->id,
        ], $this->client);
    }
}
