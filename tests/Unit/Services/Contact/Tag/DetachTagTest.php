<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Contact\Tag;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Services\Contact\Tag\DetachTag;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DetachTagTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_detachs_a_tag()
    {
        $contact = factory(Contact::class)->create([]);

        $tag = factory(Tag::class)->create([
            'account_id' => $contact->account->id,
        ]);

        $contact->tags()->syncWithoutDetaching([
            $tag->id => [
                'account_id' => $contact->account_id,
            ],
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ];

        app(DetachTag::class)->execute($request);

        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
        ];

        $this->expectException(ValidationException::class);

        app(DetachTag::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_does_not_exist()
    {
        $account = factory(Account::class)->create();
        $tag = factory(Tag::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'contact_id' => 12322,
            'tag_id' => $tag->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(DetachTag::class)->execute($request);
    }
}
