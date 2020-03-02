<?php

namespace Tests\Unit\ViewHelpers;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use App\Models\Instance\AuditLog;
use App\ViewHelpers\ContactViewHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactViewHelperTest extends TestCase
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
        $collection = ContactViewHelper::getListOfAuditLogs($logs);

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
        $information = ContactViewHelper::getWorkInformation($contact);
        $this->assertEquals(
            'No work information defined',
            $information
        );

        $contact = factory(Contact::class)->create([
            'job' => 'salesman',
        ]);
        $information = ContactViewHelper::getWorkInformation($contact);
        $this->assertEquals(
            'salesman',
            $information
        );

        $contact = factory(Contact::class)->create([
            'company' => 'microsoft',
        ]);
        $information = ContactViewHelper::getWorkInformation($contact);
        $this->assertEquals(
            'Works at microsoft',
            $information
        );

        $contact = factory(Contact::class)->create([
            'job' => 'salesman',
            'company' => 'microsoft',
        ]);
        $information = ContactViewHelper::getWorkInformation($contact);
        $this->assertEquals(
            'salesman (at microsoft)',
            $information
        );
    }

    /** @test */
    public function it_gets_the_addresses_of_the_contact()
    {
        $contact = factory(Contact::class)->create([]);
        factory(Address::class, 2)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]);

        $addresses = ContactViewHelper::getAddresses($contact);
        $this->assertEquals(
            2,
            $addresses->count()
        );
    }
}
