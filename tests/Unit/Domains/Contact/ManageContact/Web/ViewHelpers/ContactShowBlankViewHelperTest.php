<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Web\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\User;
use App\Models\Vault;
use App\Models\Contact;
use App\Models\Template;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Contact\ManageContact\Web\ViewHelpers\ContactShowBlankViewHelper;

class ContactShowBlankViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $user = User::factory()->create();
        $this->be($user);
        $template = Template::factory()->create([
            'account_id' => $vault->account_id,
        ]);
        $array = ContactShowBlankViewHelper::data($contact);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('templates', $array);
        $this->assertArrayHasKey('contact', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                0 => [
                    'id' => $template->id,
                    'name' => $template->name,
                ],
            ],
            $array['templates']->toArray()
        );

        $this->assertEquals(
            [
                'name' => $contact->getName($user),
            ],
            $array['contact']
        );

        $this->assertEquals(
            [
                'update' => env('APP_URL').'/vaults/'.$vault->id.'/contacts/'.$contact->id.'/template',
            ],
            $array['url']
        );
    }
}
