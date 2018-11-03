<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Tag;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\Tag\DestroyTags;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyTagsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_all_tags_associations()
    {
        $contact = factory(Contact::class)->create([]);

        for ($i = 0 ; $i < 5 ; $i++) {
            $tag = factory(Tag::class)->create([
                'account_id' => $contact->account->id,
            ]);
            $contact->tags()->syncWithoutDetaching([
                $tag->id => [
                    'account_id' => $contact->account_id
                ]
            ]);
        }

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
        ]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
        ];

        $destroyTagService = new DestroyTags;
        $tag = $destroyTagService->execute($request);

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

        $this->expectException(MissingParameterException::class);

        $destroyTagService = new DestroyTags;
        $tag = $destroyTagService->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_does_not_exist()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create();

        $request = [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        $destroyTagService = (new DestroyTags)->execute($request);
    }
}
