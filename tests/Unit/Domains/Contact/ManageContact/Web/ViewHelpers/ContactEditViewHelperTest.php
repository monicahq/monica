<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Web\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\User;
use App\Models\Vault;
use App\Models\Gender;
use App\Models\Contact;
use App\Models\Pronoun;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Contact\ManageContact\Web\ViewHelpers\ContactEditViewHelper;

class ContactEditViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $gender = Gender::factory()->create([
            'account_id' => $vault->account_id,
        ]);
        $pronoun = Pronoun::factory()->create([
            'account_id' => $vault->account_id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'gender_id' => $gender->id,
        ]);
        $array = ContactEditViewHelper::data($vault, $contact, $user);

        $this->assertEquals(
            4,
            count($array)
        );

        $this->assertArrayHasKey('contact', $array);
        $this->assertArrayHasKey('genders', $array);
        $this->assertArrayHasKey('pronouns', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'id' => $contact->id,
                'name' => $contact->getName($user),
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'middle_name' => $contact->middle_name,
                'nickname' => $contact->nickname,
                'maiden_name' => $contact->maiden_name,
                'gender_id' => $contact->gender_id,
                'pronoun_id' => $contact->pronoun_id,
            ],
            $array['contact']
        );

        $this->assertEquals(
            [
                0 => [
                    'id' => $gender->id,
                    'name' => $gender->name,
                    'selected' => true,
                ],
            ],
            $array['genders']->toArray()
        );

        $this->assertEquals(
            [
                0 => [
                    'id' => $pronoun->id,
                    'name' => $pronoun->name,
                    'selected' => false,
                ],
            ],
            $array['pronouns']->toArray()
        );

        $this->assertEquals(
            [
                'update' => env('APP_URL').'/vaults/'.$vault->id.'/contacts/'.$contact->id,
                'show' => env('APP_URL').'/vaults/'.$vault->id.'/contacts/'.$contact->id,
            ],
            $array['url']
        );
    }
}
