<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Contact\ManageContact\Web\ViewHelpers\ContactIndexViewHelper;
use App\Models\Avatar;
use App\Models\Contact;
use App\Models\Label;
use App\Models\Vault;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $avatar = Avatar::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $contact->avatar_id = $avatar->id;
        $contact->save();
        $label = Label::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'assigned label',
        ]);
        Label::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'unassigned label',
        ]);
        $contact->labels()->attach($label);

        $contacts = Contact::all();
        $array = ContactIndexViewHelper::data($contacts, $vault);

        $this->assertEquals(
            4,
            count($array)
        );

        $this->assertArrayHasKey('contacts', $array);
        $this->assertArrayHasKey('labels', $array);
        $this->assertArrayHasKey('current_label', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                0 => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => '123',
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$vault->id.'/contacts/'.$contact->id,
                    ],
                ],
            ],
            $array['contacts']->toArray()
        );

        $this->assertEquals(
            [
                0 => [
                    'id' => $label->id,
                    'name' => 'assigned label',
                    'count' => 1,
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$vault->id.'/contacts/labels/'.$label->id,
                    ],
                ],
            ],
            $array['labels']->toArray()
        );

        $this->assertEquals(
            [
                'contact' => [
                    'index' => env('APP_URL').'/vaults/'.$vault->id.'/contacts',
                    'create' => env('APP_URL').'/vaults/'.$vault->id.'/contacts/create',
                ],
            ],
            $array['url']
        );
    }
}
