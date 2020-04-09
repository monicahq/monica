<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\FeatureTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Instance\SpecialDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SpecialDateTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $specialDate = factory(SpecialDate::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($specialDate->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_contact()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $specialDate = factory(SpecialDate::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($specialDate->contact()->exists());
    }

    /** @test */
    public function get_age_returns_null_if_no_date_is_set()
    {
        $specialDate = new SpecialDate;
        $this->assertNull($specialDate->getAge());
    }

    /** @test */
    public function get_age_returns_null_if_year_is_unknown()
    {
        $specialDate = factory(SpecialDate::class)->make();
        $specialDate->is_year_unknown = 1;
        $specialDate->save();

        $this->assertNull($specialDate->getAge());
    }

    /** @test */
    public function get_age_returns_age()
    {
        Carbon::setTestNow(Carbon::create(2020, 2, 17, 17, 0, 0));

        $specialDate = factory(SpecialDate::class)->make();
        $specialDate->is_year_unknown = 0;
        $specialDate->date = now()->subYears(5);
        $specialDate->save();

        $this->assertEquals(
            5,
            $specialDate->getAge()
        );
    }

    /** @test */
    public function create_from_age_sets_the_right_date()
    {
        $specialDate = factory(SpecialDate::class)->make();

        $specialDate->createFromAge(100);

        $this->assertEquals(
            true,
            $specialDate->is_age_based
        );

        $this->assertEquals(
            1,
            $specialDate->date->day
        );

        $this->assertEquals(
            1,
            $specialDate->date->month
        );
    }

    /** @test */
    public function create_from_date_creates_an_approximate_date()
    {
        $specialDate = factory(SpecialDate::class)->make();

        $specialDate->createFromDate(0, 10, 10);

        $this->assertEquals(
            true,
            $specialDate->is_year_unknown
        );

        $this->assertEquals(
            10,
            $specialDate->date->day
        );

        $this->assertEquals(
            10,
            $specialDate->date->month
        );

        $this->assertEquals(
            now()->year,
            $specialDate->date->year
        );
    }

    /** @test */
    public function create_from_date_creates_an_exact_date()
    {
        $specialDate = factory(SpecialDate::class)->make();

        $specialDate->createFromDate(2019, 10, 10);

        $this->assertEquals(
            false,
            $specialDate->is_year_unknown
        );

        $this->assertEquals(
            10,
            $specialDate->date->day
        );

        $this->assertEquals(
            10,
            $specialDate->date->month
        );

        $this->assertEquals(
            2019,
            $specialDate->date->year
        );
    }

    /** @test */
    public function set_contact_sets_the_contact_information()
    {
        $specialDate = factory(SpecialDate::class)->make();

        $contact = factory(Contact::class)->create();

        $specialDate->setToContact($contact);

        $this->assertEquals(
            $contact->account_id,
            $specialDate->account_id
        );

        $this->assertEquals(
            $contact->id,
            $specialDate->contact_id
        );
    }

    /** @test */
    public function to_short_string_returns_date_with_year()
    {
        $specialDate = new SpecialDate;
        $specialDate->is_year_unknown = false;
        $specialDate->date = Carbon::create(2001, 5, 21);

        $this->assertEquals(
            'May 21, 2001',
            $specialDate->toShortString()
        );
    }

    /** @test */
    public function to_short_string_returns_date_without_year()
    {
        $specialDate = new SpecialDate;
        $specialDate->is_year_unknown = true;
        $specialDate->date = Carbon::create(2001, 5, 21);

        $this->assertEquals(
            'May 21',
            $specialDate->toShortString()
        );
    }
}
