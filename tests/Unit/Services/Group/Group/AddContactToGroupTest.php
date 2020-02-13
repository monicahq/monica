<?php

namespace Tests\Unit\Services\Group\Group;

use Tests\TestCase;
use App\Models\Group\Group;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Group\Group\AddContactToGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddContactToGroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_attaches_contact_to_the_group()
    {
        $group = factory(Group::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $group->account_id,
        ]);

        $request = [
            'account_id' => $group->account_id,
            'group_id' => $group->id,
            'contact_id' => $contact->id,
        ];

        $group = app(AddContactToGroup::class)->execute($request);

        $this->assertDatabaseHas('contact_group', [
            'group_id' => $group->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertInstanceOf(
            Group::class,
            $group
        );

        // calling the service a second time should work but
        // it should be added once
        $group = app(AddContactToGroup::class)->execute($request);

        $this->assertEquals(
            1,
            DB::table('contact_group')
                ->where('contact_id', $contact->id)
                ->count()
        );
    }

    /** @test */
    public function it_fails_if_the_contact_is_not_linked_to_the_account()
    {
        $group = factory(Group::class)->create([]);
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $group->account_id,
            'group_id' => $group->id,
            'contact_id' => $contact->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(AddContactToGroup::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $group = factory(Group::class)->create([]);
        $contactA = factory(Contact::class)->create([
            'account_id' => $group->account_id,
        ]);

        $request = [
            'group_id' => $group->id,
            'contacts' => [$contactA->id],
        ];

        $this->expectException(ValidationException::class);
        app(AddContactToGroup::class)->execute($request);
    }
}
