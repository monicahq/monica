<?php

namespace Tests\Unit\Controllers\Settings;

use Tests\FeatureTestCase;
use App\Models\Contact\Gender;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GendersControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected $jsonStructure = [
        'id',
        'name',
        'type',
        'isDefault',
        'numberOfContacts',
    ];

    protected $typesJsonStructure = [
        'id',
        'name',
    ];

    /** @test */
    public function it_gets_the_list_of_genders()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/settings/personalization/genders');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonStructure,
        ]);

        $this->assertCount(
            3,
            $response->decodeResponseJson()
        );
    }

    /** @test */
    public function it_gets_the_list_of_genderTypes()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/settings/personalization/genderTypes');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->typesJsonStructure,
        ]);

        $this->assertCount(
            5,
            $response->decodeResponseJson()
        );
    }

    /** @test */
    public function it_stores_a_new_gender()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/settings/personalization/genders', [
            'name' => 'gender',
            'type' => 'O',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure($this->jsonStructure);

        $this->assertDataBaseHas('genders', [
            'account_id' => $user->account_id,
            'name' => 'gender',
            'type' => 'O',
        ]);
    }

    /** @test */
    public function it_updates_a_gender()
    {
        $user = $this->signin();

        $gender = $user->account->genders()->first();

        $response = $this->json('PUT', '/settings/personalization/genders/'.$gender->id, [
            'name' => 'gender',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure($this->jsonStructure);

        $this->assertDataBaseHas('genders', [
            'account_id' => $user->account_id,
            'id' => $gender->id,
            'name' => 'gender',
        ]);
    }

    /** @test */
    public function it_replaces_a_gender()
    {
        $user = $this->signin();

        $genders = $user->account->genders()->get();
        $gender1 = $genders[0]->id;
        $gender2 = $genders[1]->id;

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'gender_id' => $gender1,
        ]);

        $response = $this->json('DELETE', '/settings/personalization/genders/'.$gender1.'/replaceby/'.$gender2);

        $this->expectObjectDeleted($response, $genders[0]->id);

        $this->assertDataBaseMissing('genders', [
            'account_id' => $user->account_id,
            'id' => $genders[0]->id,
        ]);
        $this->assertDataBaseHas('contacts', [
            'account_id' => $user->account_id,
            'id' => $contact->id,
            'gender_id' => $gender2,
        ]);
    }

    /** @test */
    public function it_replaces_a_gender_with_error()
    {
        $user = $this->signin();
        $gender1 = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender2 = factory(Gender::class)->create();

        $response = $this->json('DELETE', '/settings/personalization/genders/'.$gender1->id.'/replaceby/'.$gender2->id);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Please choose a valid gender from the list.',
        ]);
    }

    /** @test */
    public function it_destroys_a_gender()
    {
        $user = $this->signin();

        $gender = $user->account->genders()->first();

        $response = $this->json('DELETE', '/settings/personalization/genders/'.$gender->id);

        $this->expectObjectDeleted($response, $gender->id);

        $this->assertDataBaseMissing('genders', [
            'account_id' => $user->account_id,
            'id' => $gender->id,
        ]);
    }

    /** @test */
    public function it_updates_the_default_gender()
    {
        $user = $this->signin();

        $gender = $user->account->genders()->first();

        $this->assertNull($user->account->default_gender_id);

        $response = $this->json('PUT', '/settings/personalization/genders/default/'.$gender->id);

        $response->assertStatus(200);
        $response->assertJsonStructure($this->jsonStructure);

        $this->assertEquals($gender->id, $user->account->default_gender_id);
    }
}
