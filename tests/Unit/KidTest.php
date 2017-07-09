<?php

namespace Tests\Unit;

use App\Kid;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class KidTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetAgeWithNoBirthdateSet()
    {
        $kid = new Kid;
        $kid->birthdate = null;

        $this->assertNull($kid->age);
    }

    public function testGetAgeWithBirthdateSet()
    {
        $dateFiveYearsAgo = Carbon::now()->subYears(5);

        $kid = new Kid;
        $kid->birthdate = $dateFiveYearsAgo;

        $this->assertEquals(
            5,
            $kid->getAge()
        );
    }

    public function testGetFirstnameReturnsNullWhenUndefined()
    {
        $kid = new Kid;

        $this->assertNull($kid->getFirstName());
    }

    public function testGetFirstnameReturnsNameWhenDefined()
    {
        $kid = new Kid;
        $kid->first_name = 'Peter';

        $this->assertEquals(
            'Peter',
            $kid->getFirstName()
        );
    }
}
