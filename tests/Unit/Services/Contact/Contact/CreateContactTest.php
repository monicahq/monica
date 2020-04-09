<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Queue;
use App\Jobs\AuditLog\LogAccountAudit;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\CreateContact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateContactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_a_contact()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $user->id,
            'first_name' => 'john',
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $gender->id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => false,
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ];

        $contact = app(CreateContact::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account_id,
            'first_name' => 'john',
        ]);

        // check that a default color has been set
        $this->assertNotNull($contact->default_avatar_color);

        // check that the default avatar has been generated
        $this->assertNotNull($contact->avatar_adorable_uuid);
        $this->assertNotNull($contact->avatar_adorable_url);
        $this->assertNotNull($contact->avatar_default_url);
        $this->assertInstanceOf(
            Contact::class,
            $contact
        );
    }

    /** @test */
    public function it_stores_a_contact_and_triggers_an_audit_log()
    {
        Queue::fake();

        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $user->id,
            'first_name' => 'john',
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $gender->id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => false,
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ];

        $contact = app(CreateContact::class)->execute($request);

        Queue::assertPushed(LogAccountAudit::class, function ($job) use ($contact, $user) {
            return $job->auditLog['action'] === 'contact_created' &&
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
    public function it_stores_a_contact_without_gender()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $user->id,
            'first_name' => 'john',
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => false,
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ];

        $contact = app(CreateContact::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account_id,
            'first_name' => 'john',
            'gender_id' => null,
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $gender->id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => false,
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ];

        $this->expectException(ValidationException::class);
        app(CreateContact::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_account_doesnt_exist()
    {
        $gender = factory(Gender::class)->create([]);

        $request = [
            'account_id' => 111111111,
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $gender->id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => false,
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ];

        $this->expectException(ValidationException::class);
        app(CreateContact::class)->execute($request);
    }
}
