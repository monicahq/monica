<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Group\Group;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $group = factory(Group::class)->create([]);

        $this->assertTrue($group->account()->exists());
    }

    /** @test */
    public function it_has_many_contacts()
    {
        $group = factory(Group::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $group->account_id,
        ]);

        $group->contacts()->sync([$contact->id]);

        $this->assertTrue($group->contacts()->exists());
    }
}
