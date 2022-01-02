<?php

namespace Tests\Unit\Services\Account\Settings;

use Tests\TestCase;
use Mockery\MockInterface;
use App\Jobs\ExportAccount;
use App\Models\Account\ExportJob;
use Illuminate\Support\Facades\Storage;
use App\Notifications\ExportAccountDone;
use Illuminate\Support\Facades\Notification;
use App\Services\Account\Settings\JsonExportAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExportAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_exports_account()
    {
        Notification::fake();

        Storage::fake();
        $fake = Storage::fake('local');
        $fake->put('temp/test.json', 'null');

        $job = ExportJob::factory()->create();

        $this->mock(JsonExportAccount::class, function (MockInterface $mock) use ($job) {
            $mock->shouldReceive('execute')
                ->once()
                ->with([
                    'account_id' => $job->account_id,
                    'user_id' => $job->user_id
                ])
                ->andReturn('temp/test.json');
        });

        ExportAccount::dispatchSync($job);
        $job->refresh();

        Storage::disk('public')->assertExists($job->filename);

        Notification::assertSentTo(
            [$job->user], ExportAccountDone::class
        );
    }
}
