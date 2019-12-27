<?php

namespace Tests\Unit\Services\Family\Family;

use Tests\TestCase;
use App\Models\Family\Family;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;
use App\Services\Family\Family\AttachContactToFamily;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AttachContactToFamilyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_attaches_contacts()
    {
        $family = factory(Family::class)->create([]);
        $contactA = factory(Contact::class)->create([
            'account_id' => $family->account_id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $family->account_id,
        ]);
        $contactC = factory(Contact::class)->create([
            'account_id' => $family->account_id,
        ]);

        $request = [
            'account_id' => $family->account_id,
            'family_id' => $family->id,
            'contacts' => [$contactA->id, $contactB->id, $contactC->id],
        ];

        $family = app(AttachContactToFamily::class)->execute($request);

        $this->assertDatabaseHas('contact_family', [
            'family_id' => $family->id,
            'contact_id' => $contactA->id,
        ]);

        $this->assertDatabaseHas('contact_family', [
            'family_id' => $family->id,
            'contact_id' => $contactB->id,
        ]);

        $this->assertDatabaseHas('contact_family', [
            'family_id' => $family->id,
            'contact_id' => $contactC->id,
        ]);

        $this->assertInstanceOf(
            Family::class,
            $family
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $family = factory(Family::class)->create([]);
        $contactA = factory(Contact::class)->create([
            'account_id' => $family->account_id,
        ]);

        $request = [
            'family_id' => $family->id,
            'contacts' => [$contactA->id],
        ];

        $this->expectException(ValidationException::class);
        app(AttachContactToFamily::class)->execute($request);
    }
}
