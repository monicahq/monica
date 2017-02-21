<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;
use App\SignificantOther;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SignificantOtherTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetFirstnameReturnsNullWhenUndefined()
    {
        $significantOther = new SignificantOther;

        $this->assertNull($significantOther->getFirstName());
    }

    public function testGetFirstnameReturnsNameWhenDefined()
    {
        $significantOther = new SignificantOther;
        $significantOther->first_name = encrypt('Peter');

        $this->assertEquals(
            'Peter',
            $significantOther->getFirstName()
        );
    }

    public function testGetLastnameReturnsNullWhenUndefined()
    {
        $significantOther = new SignificantOther;

        $this->assertNull($significantOther->getLastName());
    }

    public function testGetLastnameReturnsNameWhenDefined()
    {
        $significantOther = new SignificantOther;
        $significantOther->last_name = encrypt('Jackson');

        $this->assertEquals(
            'Jackson',
            $significantOther->getLastName()
        );
    }

    public function testGetCompleteName()
    {
        $significantOther = new SignificantOther;
        $significantOther->first_name = encrypt('Peter');
        $significantOther->last_name = encrypt('Gregory');

        $this->assertEquals(
            'Peter Gregory',
            $significantOther->getCompleteName()
        );

        $significantOther = new SignificantOther;
        $significantOther->first_name = encrypt('Peter');
        $significantOther->last_name = null;

        $this->assertEquals(
            'Peter',
            $significantOther->getCompleteName()
        );
    }

    public function testGetBirthdateReturnsNullIfNoBirthdateIsDefined()
    {
        $significantOther = new SignificantOther;

        $this->assertNull($significantOther->getBirthdate());
    }

    public function testGetBirthdateReturnsCarbonObjectIfBirthdateDefined()
    {
        $significantOther = factory(\App\SignificantOther::class)->create();

        $this->assertInstanceOf(Carbon::class, $significantOther->getBirthdate());
    }

    public function testGetAgeReturnsFalseIfNoBirthdateIsDefinedForContact()
    {
        $significantOther = new SignificantOther;
        $significantOther->birthdate = null;

        $this->assertNull(
            $significantOther->getAge()
        );
    }

    public function testGetAgeReturnsAnAgeIfBirthdateIsDefined()
    {
        $dateFiveYearsAgo = Carbon::now()->subYears(25);

        $significantOther = new SignificantOther;
        $significantOther->birthdate = $dateFiveYearsAgo;

        $this->assertEquals(
            25,
            $significantOther->getAge()
        );
    }

    public function testIsBirthdateApproximate()
    {
        $significantOther = new SignificantOther;
        $significantOther->is_birthdate_approximate = 'true';

        $this->assertEquals(
            'true',
            $significantOther->isBirthdateApproximate()
        );
    }
}
