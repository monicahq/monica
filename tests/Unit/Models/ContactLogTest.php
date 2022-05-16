<?php

namespace Tests\Unit\Models;

use App\Models\ContactLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactLogTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_a_contact()
    {
        $log = ContactLog::factory()->create();
        $this->assertTrue($log->contact()->exists());
    }

    /** @test */
    public function it_belongs_to_an_author()
    {
        $log = ContactLog::factory()->create();
        $this->assertTrue($log->author()->exists());
    }

    /** @test */
    public function it_returns_the_object_attribute(): void
    {
        $log = ContactLog::factory()->create([]);
        $this->assertEquals(
            1,
            $log->object->{'user'}
        );
    }
}
