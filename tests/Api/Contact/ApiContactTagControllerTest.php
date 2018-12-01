<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

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

        $this->expectDataError($response, ['The tags field is required.']);
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
        $tagId1 = $response->json('data.tags.0.id');
        $tagId2 = $response->json('data.tags.1.id');

        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tagId1,
            'name' => 'very-specific-tag-name',
        ]);

        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tagId2,
            'name' => 'very-specific-tag-name-2',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tagId1,
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tagId2,
        ]);
    }

    public function test_a_list_of_tags_are_required_to_remove_a_tag_from_a_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', "/api/contacts/{$contact->id}/unsetTag");

        $this->expectDataError($response, ['The tags field is required.']);
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

        $contact->tags()->syncWithoutDetaching([
            $tag->id => [
                'account_id' => $contact->account_id,
            ],
            $tag2->id => [
                'account_id' => $contact->account_id,
            ],
        ]);

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

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag2->id,
            'account_id' => $user->account->id,
        ]);
        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
            'account_id' => $user->account->id,
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

        $contact->tags()->syncWithoutDetaching([
            $tag->id => [
                'account_id' => $contact->account_id,
            ],
            $tag2->id => [
                'account_id' => $contact->account_id,
            ],
            $tag3->id => [
                'account_id' => $contact->account_id,
            ],
        ]);

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
        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'account_id' => $user->account->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'account_id' => $user->account->id,
            'tag_id' => $tag2->id,
        ]);
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'account_id' => $user->account->id,
            'tag_id' => $tag3->id,
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

        $contact->tags()->syncWithoutDetaching([
            $tag->id => [
                'account_id' => $contact->account_id,
            ],
            $tag2->id => [
                'account_id' => $contact->account_id,
            ],
        ]);

        $response = $this->json('POST', "/api/contacts/{$contact->id}/unsetTags");

        $response->assertStatus(200);

        $response->assertJsonMissing([
            'name' => $tag2->name,
        ]);

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'account_id' => $user->account->id,
        ]);
    }
}
