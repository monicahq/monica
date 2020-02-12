<?php

namespace Tests\Unit\Services\Contact\Description;

use Tests\TestCase;
use App\Jobs\LogAccountAudit;
use App\Jobs\LogEmployeeAudit;
use App\Models\Company\Employee;
use App\Models\Contact\Contact;
use App\Models\User\User;
use App\Services\Contact\Description\SetPersonalDescription;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SetPersonalDescriptionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sets_a_personal_description(): void
    {
        Queue::fake();

        $contact = factory(Contact::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $request = [
            'account_id' => $contact->account_id,
            'author_id' => $user->id,
            'contact_id' => $contact->id,
            'description' => 'This is just great',
        ];

        $contact = (new SetPersonalDescription)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $contact->account_id,
            'id' => $contact->id,
            'description' => 'This is just great',
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'first_name' => 'Dwight',
        ];

        $this->expectException(ValidationException::class);
        (new SetPersonalDescription)->execute($request);
    }
}
