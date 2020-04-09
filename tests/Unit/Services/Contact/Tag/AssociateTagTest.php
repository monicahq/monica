<?php

namespace Tests\Unit\Services\Contact\Tag;

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

    /** @test */
    public function it_sets_a_non_english_tag_to_a_contact_when_tag_doesnt_exist_yet()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'name' => '朋友',
        ];

        $tag = app(AssociateTag::class)->execute($request);

        $this->assertDatabaseHas('tags', [
            'account_id' => $contact->account_id,
            'name' => '朋友',
            'name_slug' => '朋友',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        $this->assertInstanceOf(
            Tag::class,
            $tag
        );
    }

    /** @test */
    public function it_sets_a_tag_to_a_contact_when_tag_doesnt_exist_yet()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'name' => 'Central Perk',
        ];

        $tag = app(AssociateTag::class)->execute($request);

        $this->assertDatabaseHas('tags', [
            'account_id' => $contact->account_id,
            'name' => 'Central Perk',
            'name_slug' => 'central-perk',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        $this->assertInstanceOf(
            Tag::class,
            $tag
        );
    }

    /** @test */
    public function it_sets_a_tag_to_a_contact_when_tag_does_exist_yet()
    {
        $contact = factory(Contact::class)->create([]);
        $tag = factory(Tag::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $this->assertDatabaseHas('tags', [
            'account_id' => $contact->account_id,
            'name' => $tag->name,
            'name_slug' => $tag->name_slug,
        ]);

        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        $request = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'name' => 'Central Perk',
        ];

        $tag = app(AssociateTag::class)->execute($request);

        $this->assertDatabaseHas('tags', [
            'account_id' => $contact->account_id,
            'name' => 'Central Perk',
            'name_slug' => 'central-perk',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        $this->assertInstanceOf(
            Tag::class,
            $tag
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
            'contact_id' => 2,
        ];

        $this->expectException(ValidationException::class);

        app(AssociateTag::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_does_not_exist()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create();

        $request = [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'name' => 'Central Perk',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(AssociateTag::class)->execute($request);
    }
}
