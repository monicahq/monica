<?php

namespace Tests\Unit\ViewHelpers;

use Tests\TestCase;
use App\Models\User\User;
use App\Helpers\AuditLogHelper;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Tag;
use App\Models\Instance\AuditLog;
use App\ViewHelpers\ContactListHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactListHelperTest extends TestCase
{
    //use DatabaseTransactions;

    /** @test */
    public function it_gets_a_list_of_tags_with_the_number_of_contacts_associated_with_them()
    {
        $account = factory(Account::class)->create();

        // create 2 tags with 3 and 2 contacts respectively
        $tagA = factory(Tag::class)->create([
            'account_id' => $account->id,
        ]);
        for ($i = 0; $i < 3; $i++) {
            $contact = factory(Contact::class)->create([
                'account_id' => $account->id,
            ]);
            $contact->tags()->syncWithoutDetaching([$tagA->id => ['account_id' => $account->id]]);
        }
        $tagB = factory(Tag::class)->create([
            'account_id' => $account->id,
        ]);
        for ($i = 0; $i < 2; $i++) {
            $contact = factory(Contact::class)->create([
                'account_id' => $account->id,
            ]);
            $contact->tags()->syncWithoutDetaching([$tagB->id => ['account_id' => $account->id]]);
        }

        $collection = ContactListHelper::getListOfTags($account);

        $this->assertEquals(
            2,
            $collection->count()
        );

        $this->assertTrue($collection->contains('id', $tagA->id));
        $this->assertTrue($collection->contains('id', $tagB->id));
        $this->assertTrue($collection->contains('name', $tagA->name));
        $this->assertTrue($collection->contains('name', $tagB->name));
        $this->assertTrue($collection->contains('count', 3));
        $this->assertTrue($collection->contains('count', 2));

    }
}
