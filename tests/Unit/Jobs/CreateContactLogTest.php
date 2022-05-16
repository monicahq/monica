<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CreateContactLog;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CreateContactLogTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_logs_a_contact_log(): void
    {
        $regis = User::factory()->create();
        $ross = Contact::factory()->create();

        $request = [
            'contact_id' => $ross->id,
            'author_id' => $regis->id,
            'author_name' => $regis->name,
            'action_name' => 'contact_created',
            'objects' => json_encode([
                'contact_name' => $regis->name,
            ]),
        ];

        CreateContactLog::dispatch($request);

        $this->assertDatabaseHas('contact_logs', [
            'contact_id' => $ross->id,
            'author_id' => $regis->id,
            'author_name' => $regis->name,
            'action_name' => 'contact_created',
            'objects' => json_encode([
                'contact_name' => $regis->name,
            ]),
        ]);
    }
}
