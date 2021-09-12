<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ConversationTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * Returns an array containing a user object along with
     * a contact for that user.
     *
     * @return array
     */
    private function fetchUser()
    {
        $user = $this->signIn();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        return [$user, $contact];
    }

    public function test_user_can_add_a_conversation()
    {
        [$user, $contact] = $this->fetchUser();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $params = [
            'conversationDateRadio' => 'another',
            'conversationDate' => '2019-08-12',
            'contactFieldTypeId' => $contactFieldType->id,
            'messages' => '1',
            'who_wrote_1' => 'me',
            'content_1' => 'test',
        ];

        $response = $this->post('/people/'.$contact->hashID().'/conversations', $params);

        $response->assertStatus(302);

        $this->assertDatabaseHas('conversations', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'content' => 'test',
            'written_by_me' => true,
            'written_at' => '2019-08-12',
        ]);
    }

    public function test_user_cannot_add_a_conversation_without_message()
    {
        [$user, $contact] = $this->fetchUser();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $params = [
            'conversationDateRadio' => 'today',
            'contactFieldTypeId' => $contactFieldType->id,
        ];

        $response = $this->post('/people/'.$contact->hashID().'/conversations', $params, [
            'HTTP_REFERER' => 'back',
        ]);

        $response->assertStatus(302);

        $response->assertRedirect('back');
        $response->assertSessionHasErrors(['messages' => 'You must add at least one message.']);
    }

    public function test_user_can_update_a_conversation()
    {
        [$user, $contact] = $this->fetchUser();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $conversation = factory(Conversation::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);
        $message = factory(Message::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'content' => 'test',
            'written_by_me' => true,
            'written_at' => '2019-08-12',
        ]);

        $params = [
            'conversationDateRadio' => 'another',
            'conversationDate' => '2019-08-01',
            'contactFieldTypeId' => $contactFieldType->id,
            'messages' => '1',
            'who_wrote_1' => 'me',
            'content_1' => 'bla bla',
        ];

        $response = $this->put('/people/'.$contact->hashID().'/conversations/'.$conversation->hashID(), $params);

        $response->assertStatus(302);

        $this->assertDatabaseHas('conversations', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'content' => 'bla bla',
            'written_by_me' => true,
            'written_at' => '2019-08-01',
        ]);
    }

    public function test_user_cannot_update_a_conversation_without_message()
    {
        [$user, $contact] = $this->fetchUser();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $conversation = factory(Conversation::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);
        $message = factory(Message::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'content' => 'test',
            'written_by_me' => true,
            'written_at' => '2019-08-12',
        ]);

        $params = [
            'conversationDateRadio' => 'today',
            'contactFieldTypeId' => $contactFieldType->id,
        ];

        $response = $this->put('/people/'.$contact->hashID().'/conversations/'.$conversation->hashID(), $params, [
            'HTTP_REFERER' => 'back',
        ]);

        $response->assertStatus(302);

        $response->assertRedirect('back');
        $response->assertSessionHasErrors(['messages' => 'You must add at least one message.']);
    }
}
