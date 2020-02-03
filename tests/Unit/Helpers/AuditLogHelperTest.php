<?php

namespace Tests\Unit\Helpers;

use App\Helpers\AuditLogHelper;
use Tests\TestCase;
use App\Helpers\LogHelper;
use App\Models\Instance\AuditLog;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuditLogHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_the_string_explaining_the_audit_log(): void
    {
        $michael = factory(User::class)->create([]);

        $auditLog = factory(AuditLog::class)->create([
            'action' => 'contact_created',
            'objects' => json_encode([
                'author_id' => $michael->id,
                'contact_name' => $michael->first_name,
                'contact_id' => $michael->id,
            ]),
        ]);

        $sentence = AuditLogHelper::processAuditLog($auditLog);

        $this->assertIsString($sentence);

        $this->assertEquals(
            trans('app.log_contact_created'),
            $sentence
        );
    }
}
