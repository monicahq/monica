<?php

namespace Tests\Unit;

use App\Reminder;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetTitleReturnsNullIfNotDefined()
    {
        $reminder = new Reminder;

        $this->assertNull($reminder->getTitle());
    }

    public function testGetTitleReturnsStringIfDefined()
    {
        $reminder = new Reminder;
        $reminder->title = 'this is a test';

        $this->assertInternalType('string', $reminder->getTitle());
    }

    public function testGetDescriptionReturnsNullIfNotDefined()
    {
        $reminder = new Reminder;

        $this->assertNull($reminder->getDescription());
    }

    public function testGetDescriptionReturnsStringIfDefined()
    {
        $reminder = new Reminder;
        $reminder->description = 'this is a test';

        $this->assertInternalType('string', $reminder->getDescription());
    }

    public function testGetNextExpectedDateReturnsString()
    {
        $reminder = new Reminder;
        $reminder->next_expected_date = '2017-01-01 10:10:10';

        $this->assertEquals(
            '2017-01-01',
            $reminder->getNextExpectedDate()
        );
    }
}
