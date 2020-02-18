<?php

namespace Tests\Unit\ViewHelpers;

use Tests\TestCase;
use App\Models\Contact\Tag;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Instance\AuditLog;
use App\Models\User\User;
use App\ViewHelpers\ContactHelper;
use App\ViewHelpers\ContactListHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_list_of_audit_logs()
    {
        $user = factory(User::class)->create([]);

        factory(AuditLog::class, 2)->create([
            'account_id' => $user->account_id,
            'about_contact_id' => null,
            'objects' => '{"contact_name":"roger moore","contact_id":123456789}',
        ]);

        $logs = $user->account->auditLogs;
        $collection = ContactHelper::getListOfAuditLogs($logs);

        $this->assertEquals(
            2,
            $collection->count()
        );

        $this->assertEquals(
            'logs.contact_log_account_created',
            $collection[0]['description']
        );
    }

    /** @test */
    public function it_gets_the_work_information()
    {
        $contact = factory(Contact::class)->create([]);
        $information = ContactHelper::getWorkInformation($contact);
        $this->assertEquals(
            'No work information defined',
            $information
        );

        $contact = factory(Contact::class)->create([
            'job' => 'salesman',
        ]);
        $information = ContactHelper::getWorkInformation($contact);
        $this->assertEquals(
            'salesman',
            $information
        );

        $contact = factory(Contact::class)->create([
            'company' => 'microsoft',
        ]);
        $information = ContactHelper::getWorkInformation($contact);
        $this->assertEquals(
            'Works at microsoft',
            $information
        );

        $contact = factory(Contact::class)->create([
            'job' => 'salesman',
            'company' => 'microsoft',
        ]);
        $information = ContactHelper::getWorkInformation($contact);
        $this->assertEquals(
            'salesman (at microsoft)',
            $information
        );
    }
}
