<?php

namespace Tests\Unit\Services\Contact\Avatar;

use Tests\TestCase;
use App\Models\Account\Photo;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Services\Contact\Avatar\UpdateAvatar;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateAvatarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_the_avatar_with_gravatar()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'source' => 'gravatar',
        ];

        $contact = app(UpdateAvatar::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_source' => 'gravatar',
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );
    }

    public function test_it_updates_the_avatar_with_default_avatar()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'source' => 'default',
        ];

        $contact = app(UpdateAvatar::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_source' => 'default',
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );
    }

    public function test_it_updates_the_avatar_with_adorable()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'source' => 'adorable',
        ];

        $contact = app(UpdateAvatar::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_source' => 'adorable',
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );
    }

    public function test_it_updates_the_avatar_with_existing_photo()
    {
        $contact = factory(Contact::class)->create([]);
        $photo = factory(Photo::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'source' => 'photo',
            'photo_id' => $photo->id,
        ];

        $contact = app(UpdateAvatar::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_source' => 'photo',
            'avatar_photo_id' => $photo->id,
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
        ];

        $this->expectException(ValidationException::class);
        app(UpdateAvatar::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'source' => 'adorable',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateAvatar::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_photo_not_linked_to_account()
    {
        // Case: photo doesn't exist
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'source' => 'photo',
            'photo_id' => 1234,
        ];

        $this->expectException(ModelNotFoundException::class);
        $contact = app(UpdateAvatar::class)->execute($request);

        // Case: photo exists but belongs to another account
        $photo = factory(Photo::class)->create([
            'account_id' => 1234,
        ]);
        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'source' => 'photo',
            'photo_id' => $photo->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateAvatar::class)->execute($request);
    }
}
