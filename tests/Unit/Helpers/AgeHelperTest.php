<?php

namespace Tests\Unit\Helpers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Helpers\AgeHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AgeHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_age_based_on_a_complete_date(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->assertEquals(
            36,
            AgeHelper::getAge('1981-10-29')
        );
    }

    /** @test */
    public function it_gets_the_age_based_on_a_year(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->assertEquals(
            48,
            AgeHelper::getAge('1970')
        );
    }

    /** @test */
    public function it_cant_get_the_age_based_on_a_month_or_day(): void
    {
        $this->assertNull(
            AgeHelper::getAge('10-02')
        );
    }

    /** @test */
    public function it_cant_get_the_age_if_the_date_is_not_set(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $this->assertNull(
            AgeHelper::getAge('')
        );
    }

    /** @test */
    public function it_gets_the_date_based_on_a_complete_date(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = User::factory()->create();
        $this->assertEquals(
            'Oct 29, 1981',
            AgeHelper::formatDate('1981-10-29', $user)
        );
    }

    /** @test */
    public function it_gets_the_date_based_on_a_year(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = User::factory()->create();
        $this->assertEquals(
            '1970',
            AgeHelper::formatDate('1970', $user)
        );
    }

    /** @test */
    public function it_gets_the_date_based_on_a_month_or_day(): void
    {
        $user = User::factory()->create();
        $this->assertEquals(
            'Oct 02',
            AgeHelper::formatDate('10-02', $user)
        );
    }

    /** @test */
    public function it_cant_get_the_date_if_the_date_is_not_set(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = User::factory()->create();
        $this->assertNull(
            AgeHelper::formatDate('', $user)
        );
    }
}
