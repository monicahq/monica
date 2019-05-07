<?php

namespace Tests\Commands\Scheduling;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Instance\Cron;
use App\Console\Scheduling\CronEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CronEventTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_get_command()
    {
        $cron = factory(Cron::class)->create();

        $event = CronEvent::command($cron->command);

        $this->assertEquals($event->cron()->id, $cron->id);
    }

    public function test_now_not_due()
    {
        $cron = factory(Cron::class)->create();
        $event = new CronEvent($cron);

        $this->assertFalse($event->isDue());
    }

    public function test_next_minute_is_due()
    {
        Carbon::setTestNow(Carbon::create(2019, 5, 1, 7, 0, 0));

        $cron = factory(Cron::class)->create();
        $event = new CronEvent($cron);

        $this->assertFalse($event->isDue());

        Carbon::setTestNow(Carbon::create(2019, 5, 1, 7, 1, 0));

        $this->assertTrue($event->isDue());

        $this->assertDatabaseHas('crons', [
            'command' => $cron->command,
            'last_run' => '2019-05-01 07:01:00',
        ]);
    }

    public function test_hourly_cron()
    {
        Carbon::setTestNow(Carbon::create(2019, 5, 1, 7, 0, 0));

        $cron = factory(Cron::class)->create();
        $event = new CronEvent($cron);
        $event->hourly();

        $this->assertFalse($event->isDue());

        Carbon::setTestNow(Carbon::create(2019, 5, 1, 8, 22, 0));

        $this->assertTrue($event->isDue());

        $this->assertDatabaseHas('crons', [
            'command' => $cron->command,
            'last_run' => '2019-05-01 08:22:00',
        ]);

        Carbon::setTestNow(Carbon::create(2019, 5, 1, 8, 59, 0));

        $this->assertFalse($event->isDue());

        Carbon::setTestNow(Carbon::create(2019, 5, 1, 9, 01, 0));

        $this->assertTrue($event->isDue());

        $this->assertDatabaseHas('crons', [
            'command' => $cron->command,
            'last_run' => '2019-05-01 09:01:00',
        ]);
    }

    public function test_daily_cron()
    {
        Carbon::setTestNow(Carbon::create(2019, 5, 1, 7, 0, 0));

        $cron = factory(Cron::class)->create();
        $event = new CronEvent($cron);
        $event->daily();

        Carbon::setTestNow(Carbon::create(2019, 5, 2, 8, 10, 0));

        $this->assertTrue($event->isDue());

        $this->assertDatabaseHas('crons', [
            'command' => $cron->command,
            'last_run' => '2019-05-02 08:10:00',
        ]);

        Carbon::setTestNow(Carbon::create(2019, 5, 2, 10, 0, 0));

        $this->assertFalse($event->isDue());

        Carbon::setTestNow(Carbon::create(2019, 5, 3, 0, 0, 0));

        $this->assertTrue($event->isDue());

        $this->assertDatabaseHas('crons', [
            'command' => $cron->command,
            'last_run' => '2019-05-03 00:00:00',
        ]);
    }
}
