<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Models\User\User;
use App\Helpers\AuditLogHelper;
use App\Models\Contact\Contact;
use App\Models\Instance\AuditLog;

class AuditLogHelperTest extends TestCase
{
    /** @test */
    public function it_prepares_a_collection_of_audit_logs_for_the_settings_page()
    {
        $user = factory(User::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
            'last_name' => 'moore',
        ]);

        factory(AuditLog::class, 2)->create([
            'account_id' => $user->account_id,
            'about_contact_id' => $contact->id,
            'objects' => '{"contact_name":"'.$contact->name.'","contact_id":'.$contact->id.'}',
        ]);

        $logs = $user->account->auditLogs;
        $collection = AuditLogHelper::getCollectionOfAuditForSettings($logs);

        $this->assertEquals(
            2,
            $collection->count()
        );
    }

    /** @test */
    public function it_prepares_a_collection_of_audit_logs_without_likns_for_the_settings_page()
    {
        $user = factory(User::class)->create([]);

        factory(AuditLog::class, 2)->create([
            'account_id' => $user->account_id,
            'about_contact_id' => null,
            'objects' => '{"contact_name":"roger moore","contact_id":123456789}',
        ]);

        $logs = $user->account->auditLogs;
        $collection = AuditLogHelper::getCollectionOfAuditForSettings($logs);

        $this->assertEquals(
            2,
            $collection->count()
        );

        $this->assertEquals(
            'logs.settings_log_account_created_with_name',
            $collection[0]['description']
        );
    }
}
