<?php

namespace Tests\Unit\Domains\Contact\ManageContactName\Web\ViewHelpers;

use App\Contact\ManageContactName\Web\ViewHelpers\ModuleContactNameViewHelper;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleContactNameViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $array = ModuleContactNameViewHelper::data($contact, $user);

        $this->assertEquals(
            2,
            count($array)
        );

        $this->assertArrayHasKey('name', $array);

        $this->assertEquals(
            [
                'name' => $contact->name,
                'url' => [
                    'edit' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/edit',
                ],
            ],
            $array
        );
    }
}
