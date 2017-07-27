<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;
use App\SignificantOther;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SignificantOtherTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetFirstnameReturnsNullWhenUndefined()
    {
        $significantOther = new SignificantOther;

        $this->assertNull($significantOther->getName());
    }

    public function testGetFirstnameReturnsNameWhenDefined()
    {
        $significantOther = new SignificantOther;
        $significantOther->first_name = 'Peter';

        $this->assertEquals(
            'Peter',
            $significantOther->getName()
        );
    }

    public function testGetBirthdateReturnsEmptyCarbonInstanceIfNoBirthdateIsDefined()
    {
        $significantOther = new SignificantOther;

        $this->assertInstanceOf(Carbon::class, $significantOther->getBirthdate());
        $this->assertTrue($significantOther->getBirthdate()->isToday());
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
            $significantOther->age
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
