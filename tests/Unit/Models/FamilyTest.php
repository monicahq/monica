<?php

namespace Tests\Unit\Models;

use App\Models\Contact\Contact;
use Tests\TestCase;
use App\Models\Family\Family;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FamilyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $family = factory(Family::class)->create([]);

        $this->assertTrue($family->account()->exists());
    }

    public function test_it_has_many_contacts()
    {
        $family = factory(Family::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $family->account_id,
        ]);

        $family->contacts()->sync([$contact->id]);

        $this->assertTrue($family->contacts()->exists());
    }
}
