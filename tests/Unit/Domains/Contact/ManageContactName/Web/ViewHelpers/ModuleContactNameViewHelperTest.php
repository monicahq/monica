<?php

namespace Tests\Unit\Domains\Contact\ManageContactName\Web\ViewHelpers;

use App\Domains\Contact\ManageContactName\Web\ViewHelpers\ModuleContactNameViewHelper;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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

        $this->assertCount(3, $array);

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('is_favorite', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'name' => $contact->name,
                'is_favorite' => false,
                'url' => [
                    'edit' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/edit',
                    'toggle_favorite' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/toggle-favorite',
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_view_when_favorite(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        DB::table('contact_vault_user')->insert([
            'contact_id' => $contact->id,
            'vault_id' => $contact->vault_id,
            'user_id' => $user->id,
            'number_of_views' => 0,
            'is_favorite' => true,
        ]);

        $array = ModuleContactNameViewHelper::data($contact, $user);

        $this->assertEquals(
            [
                'name' => $contact->name,
                'is_favorite' => true,
                'url' => [
                    'edit' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/edit',
                    'toggle_favorite' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/toggle-favorite',
                ],
            ],
            $array
        );
    }
}
