<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use App\Models\Instance\AuditLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiAuditLogControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureAuditLog = [
        'id',
        'object',
        'author' => [
            'name',
        ],
        'action',
        'objects',
        'audited_at',
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function it_gets_a_list_of_audit_logs()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'first_name' => 'roger',
        ]);

        factory(AuditLog::class, 10)->create([
            'account_id' => $user->account_id,
            'about_contact_id' => $contact->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact->id.'/logs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureAuditLog],
        ]);

        $this->assertCount(
            10,
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
        ]);
    }
}
