<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ContactReminderHelper;
use App\Models\ContactReminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactReminderHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_date_based_on_a_complete_date(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = User::factory()->create();
        $reminder = ContactReminder::factory()->create([
            'day' => 29,
            'month' => 10,
            'year' => 1981,
        ]);
        $this->assertEquals(
            'Oct 29, 1981',
            ContactReminderHelper::formatDate($reminder, $user)
        );
    }

    /** @test */
    public function it_gets_the_date_based_on_a_year(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = User::factory()->create();
        $reminder = ContactReminder::factory()->create([
            'day' => null,
            'month' => null,
            'year' => 1970,
        ]);
        $this->assertNull(
            ContactReminderHelper::formatDate($reminder, $user)
        );
    }

    /** @test */
    public function it_gets_the_date_based_on_a_month_or_day(): void
    {
        $user = User::factory()->create();
        $reminder = ContactReminder::factory()->create([
            'day' => 29,
            'month' => 10,
            'year' => null,
        ]);
        $this->assertEquals(
            'Oct 29',
            ContactReminderHelper::formatDate($reminder, $user)
        );
    }

    /** @test */
    public function it_cant_get_the_date_if_the_date_is_not_set(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = User::factory()->create();
        $reminder = ContactReminder::factory()->create([
            'day' => null,
            'month' => null,
            'year' => null,
        ]);
        $this->assertNull(
            ContactReminderHelper::formatDate($reminder, $user)
        );
    }
}
