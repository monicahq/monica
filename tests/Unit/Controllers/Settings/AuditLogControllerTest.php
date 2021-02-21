<?php

namespace Tests\Unit\Controllers\Settings;

use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuditLogControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_list_of_audit_logs_for_the_account()
    {
        $user = $this->signin();

        factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->get('/settings/auditlogs');

        $response->assertStatus(200);
    }
}
