<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\User\User;
use function Safe\json_encode;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Queue;
use App\Jobs\AuditLog\LogAccountAudit;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\CreateContact;
use App\Services\Contact\Contact\UpdateWorkInformation;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateWorkInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_work_information()
    {
        Queue::fake();

        $user = factory(User::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $request = [
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'contact_id' => $contact->id,
            'job' => 'Dunder',
        ];

        $contact = app(UpdateWorkInformation::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account_id,
            'job' => 'Dunder',
            'company' => null,
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $request = [
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'contact_id' => $contact->id,
            'company' => 'Sales',
        ];

        $contact = app(UpdateWorkInformation::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account_id,
            'job' => null,
            'company' => 'Sales',
        ]);

        Queue::assertPushed(LogAccountAudit::class, function ($job) use ($contact, $user) {
            return $job->auditLog['action'] === 'contact_work_updated' &&
                $job->auditLog['author_id'] === $user->id &&
                $job->auditLog['about_contact_id'] === $contact->id &&
                $job->auditLog['should_appear_on_dashboard'] === true &&
                $job->auditLog['objects'] === json_encode([
                    'contact_name' => $contact->name,
                    'contact_id' => $contact->id,
                ]);
        });
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $user->account_id,
        ];

        $this->expectException(ValidationException::class);
        app(UpdateWorkInformation::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_account_doesnt_exist()
    {
        $user = factory(User::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $request = [
            'account_id' => 111111111,
            'author_id' => $user->id,
            'contact_id' => $contact->id,
            'job' => 'Dunder',
        ];

        $this->expectException(ValidationException::class);
        app(CreateContact::class)->execute($request);
    }
}
