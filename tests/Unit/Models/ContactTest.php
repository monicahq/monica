<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Loan;
use App\Models\Note;
use App\Models\Label;
use App\Models\Gender;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Pronoun;
use App\Models\Template;
use App\Models\ContactLog;
use App\Models\ContactReminder;
use App\Models\RelationshipType;
use App\Models\ContactInformation;
use App\Models\ContactImportantDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
}
