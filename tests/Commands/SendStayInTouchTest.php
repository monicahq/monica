<?php

namespace Tests\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\StayInTouch\ScheduleStayInTouch;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendStayInTouchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_schedules_a_stay_in_touch_job()
    {
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_trigger_date' => '2017-01-01 07:00:00',
        ]);

        $exitCode = Artisan::call('send:stay_in_touch', []);

        Bus::assertDispatched(ScheduleStayInTouch::class);
    }

    public function test_it_doesnt_schedule_stay_in_touch_jobs_if_no_date_is_found()
    {
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_trigger_date' => '2017-03-01 07:00:00',
        ]);

        $exitCode = Artisan::call('send:stay_in_touch', []);

        Bus::assertNotDispatched(ScheduleStayInTouch::class);
    }
}
