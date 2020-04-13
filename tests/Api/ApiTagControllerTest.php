<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Tag;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiTagControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonTag = [
        'id',
        'object',
        'name',
        'name_slug',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    protected $jsonStructureContactWithContactFields = [
        'id',
        'object',
        'hash_id',
        'first_name',
        'last_name',
        'gender',
        'gender_type',
        'is_starred',
        'is_partial',
        'is_dead',
        'last_called',
        'last_activity_together',
        'stay_in_touch_frequency',
        'stay_in_touch_trigger_date',
        'information' => [
            'relationships' => [
                'love' => [
                    'total',
                    'contacts',
                ],
                'family' => [
                    'total',
                    'contacts',
                ],
                'friend' => [
                    'total',
                    'contacts',
                ],
                'work' => [
                    'total',
                    'contacts',
                ],
            ],
            'dates' => [
                'birthdate' => [
                    'is_age_based',
                    'is_year_unknown',
                    'date',
                ],
                'deceased_date' => [
                    'is_age_based',
                    'is_year_unknown',
                    'date',
                ],
            ],
            'career' => [
                'job',
                'company',
            ],
            'avatar' => [
                'url',
                'source',
                'default_avatar_color',
            ],
            'food_preferences',
            'how_you_met' => [
                'general_information',
                'first_met_date' => [
                    'is_age_based',
                    'is_year_unknown',
                    'date',
                ],
                'first_met_through_contact',
            ],
        ],
        'addresses' => [],
        'tags' => [],
        'statistics' => [
            'number_of_calls',
            'number_of_notes',
            'number_of_activities',
            'number_of_reminders',
            'number_of_tasks',
            'number_of_gifts',
            'number_of_debts',
        ],
        'contactFields' => [
            '*' => [
                'id',
                'object',
                'content',
                'contact_field_type' => [
                    'id',
                    'object',
                    'name',
                    'fontawesome_icon',
                    'protocol',
                    'delible',
                    'type',
                    'account' => [
                        'id',
                    ],
                    'created_at',
                    'updated_at',
                ],
                'account' => [
                    'id',
                ],
                'contact' => [],
                'created_at',
                'updated_at',
            ],
        ],
        'notes' => [],
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function it_get_all_tags()
    {
        $user = $this->signin();
        $tag1 = factory(Tag::class)->create([
            'account_id' => $user->account_id,
        ]);
        $tag2 = factory(Tag::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/tags');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonTag,
            ],
        ]);

        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag1->id,
        ]);

        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag2->id,
        ]);
    }

    /** @test */
    public function it_gets_a_specific_tag()
    {
        $user = $this->signin();
        $tag = factory(Tag::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/tags/'.$tag->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonTag,
        ]);

        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag->id,
        ]);
    }

    /** @test */
    public function it_triggers_error_if_tag_unknown()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/tags/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_creates_a_tag()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/tags', [
            'name' => 'the tag',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonTag,
        ]);
        $tag_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag_id,
            'name' => 'the tag',
        ]);

        $this->assertGreaterThan(0, $tag_id);
        $this->assertDatabaseHas('tags', [
            'account_id' => $user->account_id,
            'id' => $tag_id,
            'name' => 'the tag',
        ]);
    }

    /** @test */
    public function it_updates_a_tag()
    {
        $user = $this->signin();
        $tag = factory(Tag::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/tags/'.$tag->id, [
            'name' => 'the tag',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonTag,
        ]);

        $tag_id = $response->json('data.id');
        $this->assertEquals($tag->id, $tag_id);

        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag_id,
            'name' => 'the tag',
        ]);

        $this->assertGreaterThan(0, $tag_id);
        $this->assertDatabaseHas('tags', [
            'account_id' => $user->account_id,
            'id' => $tag_id,
            'name' => 'the tag',
        ]);
    }

    /** @test */
    public function it_deletes_a_tag()
    {
        $user = $this->signin();
        $tag = factory(Tag::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('DELETE', '/api/tags/'.$tag->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $user->account_id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('tags', [
            'account_id' => $user->account_id,
            'id' => $tag->id,
        ]);
    }

    /** @test */
    public function it_deletes_a_tag_associated()
    {
        $user = $this->signin();
        $tag = factory(Tag::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', "/api/contacts/{$contact1->id}/setTags", ['tags' => [$tag->name]]);
        $response = $this->json('POST', "/api/contacts/{$contact2->id}/setTags", ['tags' => [$tag->name]]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
            'tag_id' => $tag->id,
        ]);

        $response = $this->json('DELETE', '/api/tags/'.$tag->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('tags', [
            'account_id' => $user->account_id,
            'id' => $tag->id,
        ]);
    }

    /** @test */
    public function it_gets_all_the_contacts_for_a_given_tag()
    {
        $user = $this->signin();

        $tag = factory(Tag::class)->create([
            'account_id' => $user->account_id,
        ]);
        factory(Contact::class, 10)->create([
            'account_id' => $user->account_id,
        ]);
        for ($i = 0; $i < 3; $i++) {
            $contact = factory(Contact::class)->create([
                'account_id' => $user->account_id,
            ]);

            $contact->tags()->sync([
                $tag->id => [
                    'account_id' => $user->account_id,
                ],
            ]);
        }

        $response = $this->json('GET', '/api/tags/'.$tag->id.'/contacts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureContactWithContactFields],
        ]);

        $this->assertCount(
            3,
            $response->decodeResponseJson()['data']
        );
    }

    /** @test */
    public function it_gets_all_the_contacts_for_a_given_tag_and_applies_pagination()
    {
        $user = $this->signin();

        $tag = factory(Tag::class)->create([
            'account_id' => $user->account_id,
        ]);
        factory(Contact::class, 10)->create([
            'account_id' => $user->account_id,
        ]);
        for ($i = 0; $i < 3; $i++) {
            $contact = factory(Contact::class)->create([
                'account_id' => $user->account_id,
            ]);

            $contact->tags()->sync([
                $tag->id => [
                    'account_id' => $user->account_id,
                ],
            ]);
        }

        $response = $this->json('GET', '/api/tags/'.$tag->id.'/contacts?limit=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureContactWithContactFields],
        ]);

        $response->assertJsonFragment([
            'total' => 3,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 3,
        ]);
    }
}
