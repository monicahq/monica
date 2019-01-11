<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Contact\Tag;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Services\Contact\Tag\AssociateTag;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AssociateTagTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_sets_a_tag_to_a_contact_when_tag_doesnt_exist_yet()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'name' => 'Central Perk',
        ];

        $associateTagService = new AssociateTag;
        $tag = $associateTagService->execute($request);

        $this->assertDatabaseHas('tags', [
            'account_id' => $contact->account->id,
            'name' => 'Central Perk',
            'name_slug' => 'central-perk',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        $this->assertInstanceOf(
            Tag::class,
            $tag
        );
    }

    public function test_it_sets_a_tag_to_a_contact_when_tag_does_exist_yet()
    {
        $contact = factory(Contact::class)->create([]);
        $tag = factory(Tag::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $this->assertDatabaseHas('tags', [
            'account_id' => $contact->account->id,
            'name' => $tag->name,
            'name_slug' => $tag->name_slug,
        ]);

        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'name' => 'Central Perk',
        ];

        $associateTagService = new AssociateTag;
        $tag = $associateTagService->execute($request);

        $this->assertDatabaseHas('tags', [
            'account_id' => $contact->account->id,
            'name' => 'Central Perk',
            'name_slug' => 'central-perk',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        $this->assertInstanceOf(
            Tag::class,
            $tag
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
            'contact_id' => 2,
        ];

        $this->expectException(ValidationException::class);

        $associateTagService = new AssociateTag;
        $tag = $associateTagService->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_does_not_exist()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create();

        $request = [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'name' => 'Central Perk',
        ];

        $this->expectException(ModelNotFoundException::class);
        $associateTagService = (new AssociateTag)->execute($request);
    }
}
