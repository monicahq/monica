<?php

namespace Tests\Unit\Controllers\Contact;

use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\FeatureTestCase;

class ContactAuditLogControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_list_of_audit_logs_for_the_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->get("/people/{$contact->hashID()}/auditlogs");

        $response->assertStatus(200);
    }
}
