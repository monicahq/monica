<?php

namespace App\Jobs;

use App\Models\Account\Place;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Exceptions\NoCoordinatesException;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Instance\Weather\GetWeatherInformation as GetWeatherInformationService;

class GetWeatherInformation implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

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
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->batching()) {
            return;
        }

        if (is_null($this->place->latitude)) {
            $this->fail(new NoCoordinatesException());
        } else {
            app(GetWeatherInformationService::class)->execute([
                'account_id' => $this->place->account_id,
                'place_id' => $this->place->id,
            ]);
        }
    }
}
