<?php

namespace Tests\Unit\Services\Group\Group;

use Tests\TestCase;
use App\Models\Group\Group;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;
use App\Services\Group\Group\AttachContactToGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AttachContactToGroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_attaches_contacts()
    {
        $group = factory(Group::class)->create([]);
        $contactA = factory(Contact::class)->create([
            'account_id' => $group->account_id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $group->account_id,
        ]);
        $contactC = factory(Contact::class)->create([
            'account_id' => $group->account_id,
        ]);

        $request = [
            'account_id' => $group->account_id,
            'group_id' => $group->id,
            'contacts' => [$contactA->id, $contactB->id, $contactC->id],
        ];

        $group = app(AttachContactToGroup::class)->execute($request);

        $this->assertDatabaseHas('contact_group', [
            'group_id' => $group->id,
            'contact_id' => $contactA->id,
        ]);

        $this->assertDatabaseHas('contact_group', [
            'group_id' => $group->id,
            'contact_id' => $contactB->id,
        ]);

        $this->assertDatabaseHas('contact_group', [
            'group_id' => $group->id,
            'contact_id' => $contactC->id,
        ]);

        $this->assertInstanceOf(
            Group::class,
            $group
        );
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
        app(AttachContactToGroup::class)->execute($request);
    }
}
