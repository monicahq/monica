<?php

namespace Tests\Unit\Models;

use App\Models\Address;
use App\Models\Avatar;
use App\Models\Call;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use App\Models\ContactInformation;
use App\Models\ContactLog;
use App\Models\ContactReminder;
use App\Models\ContactTask;
use App\Models\Gender;
use App\Models\Label;
use App\Models\Loan;
use App\Models\Note;
use App\Models\Pet;
use App\Models\Pronoun;
use App\Models\RelationshipType;
use App\Models\Template;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $ross = Contact::factory()->create();

        $this->assertTrue($ross->vault()->exists());
    }

    /** @test */
    public function it_has_one_gender()
    {
        $ross = Contact::factory()->create([
            'gender_id' => Gender::factory()->create(),
        ]);

        $this->assertTrue($ross->gender()->exists());
    }

    /** @test */
    public function it_has_one_pronoun()
    {
        $ross = Contact::factory()->create([
            'pronoun_id' => Pronoun::factory()->create(),
        ]);

        $this->assertTrue($ross->pronoun()->exists());
    }

    /** @test */
    public function it_has_one_template()
    {
        $ross = Contact::factory()->create([
            'template_id' => Template::factory()->create(),
        ]);

        $this->assertTrue($ross->template()->exists());
    }

    /** @test */
    public function it_has_many_relationships(): void
    {
        $ross = Contact::factory()->create([]);
        $monica = Contact::factory()->create();
        $relationshipType = RelationshipType::factory()->create();

        $ross->relationships()->sync([$monica->id => ['relationship_type_id' => $relationshipType->id]]);

        $this->assertTrue($ross->relationships()->exists());
    }

    /** @test */
    public function it_has_many_labels(): void
    {
        $ross = Contact::factory()->create([]);
        $label = Label::factory()->create();

        $ross->labels()->sync([$label->id]);

        $this->assertTrue($ross->labels()->exists());
    }

    /** @test */
    public function it_has_many_logs(): void
    {
        $ross = Contact::factory()->create();
        ContactLog::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->contactLogs()->exists());
    }

    /** @test */
    public function it_has_many_contact_information(): void
    {
        $ross = Contact::factory()->create();
        ContactInformation::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->contactInformation()->exists());
    }

    /** @test */
    public function it_has_many_addresses(): void
    {
        $ross = Contact::factory()->create();
        Address::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->addresses()->exists());
    }

    /** @test */
    public function it_has_many_notes(): void
    {
        $ross = Contact::factory()->create();
        Note::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->notes()->exists());
    }

    /** @test */
    public function it_has_many_dates(): void
    {
        $ross = Contact::factory()->create();
        ContactImportantDate::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->dates()->exists());
    }

    /** @test */
    public function it_has_many_reminders(): void
    {
        $ross = Contact::factory()->create();
        ContactReminder::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->reminders()->exists());
    }

    /** @test */
    public function it_has_many_loans_as_loaner(): void
    {
        $ross = Contact::factory()->create([]);
        $monica = Contact::factory()->create();
        $loan = Loan::factory()->create();

        $ross->loansAsLoaner()->sync([$loan->id => ['loanee_id' => $monica->id]]);

        $this->assertTrue($ross->loansAsLoaner()->exists());
    }

    /** @test */
    public function it_has_many_loans_as_loanee(): void
    {
        $ross = Contact::factory()->create([]);
        $monica = Contact::factory()->create();
        $loan = Loan::factory()->create();

        $ross->loansAsLoanee()->sync([$loan->id => ['loaner_id' => $monica->id]]);

        $this->assertTrue($ross->loansAsLoanee()->exists());
    }

    /** @test */
    public function it_has_one_company()
    {
        $ross = Contact::factory()->create([
            'company_id' => Company::factory()->create(),
        ]);

        $this->assertTrue($ross->company()->exists());
    }

    /** @test */
    public function it_has_many_avatars(): void
    {
        $ross = Contact::factory()->create();
        Avatar::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->avatars()->exists());
    }

    /** @test */
    public function it_has_many_tasks(): void
    {
        $ross = Contact::factory()->create();
        ContactTask::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->tasks()->exists());
    }

    /** @test */
    public function it_has_many_calls(): void
    {
        $ross = Contact::factory()->create();
        Call::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->calls()->exists());
    }

    /** @test */
    public function it_has_many_pets(): void
    {
        $ross = Contact::factory()->create();
        Pet::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->pets()->exists());
    }

    /** @test */
    public function it_gets_the_name(): void
    {
        $user = User::factory()->create([
            'name_order' => '%first_name%',
        ]);
        $contact = Contact::factory()->create([
            'first_name' => 'James',
            'last_name' => 'Bond',
            'nickname' => '007',
            'middle_name' => 'W.',
            'maiden_name' => 'Muller',
        ]);

        $this->assertEquals(
            'James',
            $contact->getName($user)
        );

        $user->update(['name_order' => '%last_name%']);
        $this->assertEquals(
            'Bond',
            $contact->getName($user)
        );

        $user->update(['name_order' => '%first_name% %last_name%']);
        $this->assertEquals(
            'James Bond',
            $contact->getName($user)
        );

        $user->update(['name_order' => '%first_name% (%maiden_name%) %last_name%']);
        $this->assertEquals(
            'James (Muller) Bond',
            $contact->getName($user)
        );

        $user->update(['name_order' => '%last_name% (%maiden_name%)  || (%nickname%) || %first_name%']);
        $this->assertEquals(
            'Bond (Muller)  || (007) || James',
            $contact->getName($user)
        );
    }

    /** @test */
    public function it_gets_the_age(): void
    {
        Carbon::setTestNow(Carbon::create(2022, 1, 1));
        $contact = Contact::factory()->create();
        $type = ContactImportantDateType::factory()->create([
            'vault_id' => $contact->vault_id,
            'internal_type' => ContactImportantDate::TYPE_BIRTHDATE,
        ]);

        $this->assertNull(
            $contact->getAge()
        );

        ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'contact_important_date_type_id' => $type->id,
            'day' => 1,
            'month' => 10,
            'year' => 2000,
        ]);

        $this->assertEquals(
            21,
            $contact->getAge()
        );
    }
}
