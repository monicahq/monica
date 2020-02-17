<?php

namespace Tests\Unit\Models;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use App\Models\Instance\AuditLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuditLogTest extends ApiTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account(): void
    {
        $auditLog = factory(AuditLog::class)->create([]);
        $this->assertTrue($auditLog->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_user(): void
    {
        $auditLog = factory(AuditLog::class)->create([]);
        $this->assertTrue($auditLog->author()->exists());
    }

    /** @test */
    public function it_belongs_to_a_contact(): void
    {
        $contact = factory(Contact::class)->create([]);
        $auditLog = factory(AuditLog::class)->create([
            'about_contact_id' => $contact->id,
        ]);
        $this->assertTrue($auditLog->contact()->exists());
    }

    /** @test */
    public function it_returns_the_object_attribute(): void
    {
        $auditLog = factory(AuditLog::class)->create([]);
        $this->assertEquals(
            1,
            $auditLog->object->{'user'}
        );
    }
}
