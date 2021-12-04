<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuditLogTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $log = AuditLog::factory()->create();
        $this->assertTrue($log->account()->exists());
    }

    /** @test */
    public function it_belongs_to_an_author()
    {
        $log = AuditLog::factory()->create();
        $this->assertTrue($log->account()->exists());
    }

    /** @test */
    public function it_returns_the_object_attribute(): void
    {
        $auditLog = AuditLog::factory()->create([]);
        $this->assertEquals(
            1,
            $auditLog->object->{'user'}
        );
    }
}
