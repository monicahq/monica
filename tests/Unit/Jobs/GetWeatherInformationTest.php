<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Mockery\MockInterface;
use App\Models\Account\Place;
use Illuminate\Bus\PendingBatch;
use App\Jobs\GetWeatherInformation;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Instance\Weather\GetWeatherInformation as GetWeatherInformationService;

class GetWeatherInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_run_job_weather_information()
    {
        $fake = Bus::fake();

        $place = factory(Place::class)->create([
            'latitude' => '34.112456',
            'longitude' => '-118.4270732',
        ]);

        $this->mock(GetWeatherInformationService::class, function (MockInterface $mock) use ($place) {
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($data) use ($place) {
                    $this->assertEquals([
                        'account_id' => $place->account_id,
                        'place_id' => $place->id,
                    ], $data);

                    return true;
                });
        });

        $pendingBatch = $fake->batch([
            $job = new GetWeatherInformation($place),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(GetWeatherInformation::class, $pendingBatch->jobs->first());

            return true;
        });

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();
    }
}
