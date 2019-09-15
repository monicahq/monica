<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Photo;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PhotoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $photo = factory(Photo::class)->create([
            'account_id' => $account->id,
        ]);
        $this->assertTrue($photo->account()->exists());
    }

    public function test_it_belongs_to_many_contacts()
    {
        $contact = factory(Contact::class)->create();
        $photo = factory(Photo::class)->create();
        $contact->photos()->sync([$photo->id]);

        $photo = factory(Photo::class)->create();
        $contact->photos()->sync([$photo->id]);

        $this->assertTrue($photo->contacts()->exists());
    }

    public function test_it_gets_the_url()
    {
        $photo = factory(Photo::class)->create();
        $this->assertEquals(
            config('app.url').'/storage/'.$photo->new_filename,
            $photo->url()
        );
    }
}
