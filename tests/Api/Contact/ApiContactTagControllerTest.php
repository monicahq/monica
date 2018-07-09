<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Contact\Tag;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiContactTagControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_tags_are_required_to_associate_tags_to_a_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', "/api/contacts/{$contact->id}/setTags");

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'message' => ['The tags field is required.'],
            'error_code' => 32,
        ]);
    }

    public function test_it_associates_tags_to_a_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', "/api/contacts/{$contact->id}/setTags", [
                            'tags' => ['very-specific-tag-name', 'very-specific-tag-name-2'],
                        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $contact->id,
            'name' => 'very-specific-tag-name-2',
        ]);
    }

    public function test_a_list_of_tags_are_required_to_remove_a_tag_from_a_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', "/api/contacts/{$contact->id}/unsetTag");

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'message' => ['The tags field is required.'],
            'error_code' => 32,
        ]);
    }

    public function test_it_removes_one_tag_from_a_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $tag = factory(Tag::class)->create([
            'account_id' => $user->account->id,
            'name' => 'friend',
        ]);
        $tag2 = factory(Tag::class)->create([
            'account_id' => $user->account->id,
            'name' => 'family',
        ]);

        $contact->setTag($tag->name);
        $contact->setTag($tag2->name);

        $response = $this->json('POST', "/api/contacts/{$contact->id}/unsetTag", [
                            'tags' => [$tag->id],
                        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $contact->id,
            'name' => $tag2->name,
        ]);

        $response->assertJsonMissing([
            'name' => $tag->name,
        ]);
    }

    public function test_it_removes_multiple_tags_from_a_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $tag = factory(Tag::class)->create([
            'account_id' => $user->account->id,
            'name' => 'friend',
        ]);
        $tag2 = factory(Tag::class)->create([
            'account_id' => $user->account->id,
            'name' => 'family',
        ]);
        $tag3 = factory(Tag::class)->create([
            'account_id' => $user->account->id,
            'name' => 'work',
        ]);

        $contact->setTag($tag->name);
        $contact->setTag($tag2->name);
        $contact->setTag($tag3->name);

        $response = $this->json('POST', "/api/contacts/{$contact->id}/unsetTag", [
                            'tags' => [$tag->id, $tag2->id],
                        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $contact->id,
            'name' => $tag3->name,
        ]);

        $response->assertJsonMissing([
            'name' => $tag2->name,
        ]);
    }

    public function test_it_removes_all_tags_from_a_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $tag = factory(Tag::class)->create([
            'account_id' => $user->account->id,
            'name' => 'friend',
        ]);
        $tag2 = factory(Tag::class)->create([
            'account_id' => $user->account->id,
            'name' => 'family',
        ]);

        $contact->setTag($tag->name);
        $contact->setTag($tag2->name);

        $response = $this->json('GET', "/api/contacts/{$contact->id}/unsetTags");

        $response->assertStatus(200);

        $response->assertJsonMissing([
            'name' => $tag2->name,
        ]);
    }
}
