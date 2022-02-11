<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Note;
use App\Models\Label;
use App\Models\Gender;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Pronoun;
use App\Models\Template;
use App\Models\ContactLog;
use App\Models\ContactDate;
use App\Models\RelationshipType;
use App\Models\ContactInformation;
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
        ContactDate::factory()->count(2)->create([
            'contact_id' => $ross->id,
        ]);

        $this->assertTrue($ross->dates()->exists());
    }
}
