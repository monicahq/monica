<?php

namespace Tests\Unit\Domains\Contact\ManagePets\Web\ViewHelpers;

use App\Domains\Contact\ManagePets\Web\ViewHelpers\ModulePetsViewHelper;
use App\Models\Contact;
use App\Models\Pet;
use App\Models\PetCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ModulePetsViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $petCategory = PetCategory::factory()->create([
            'account_id' => $user->account_id,
        ]);
        Pet::factory()->create([
            'contact_id' => $contact->id,
            'pet_category_id' => $petCategory->id,
        ]);
        $array = ModulePetsViewHelper::data($contact, $user);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('pets', $array);
        $this->assertArrayHasKey('pet_categories', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                0 => [
                    'id' => $petCategory->id,
                    'name' => $petCategory->name,
                ],
            ],
            $array['pet_categories']->toArray()
        );

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/pets',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $petCategory = PetCategory::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $pet = Pet::factory()->create([
            'contact_id' => $contact->id,
            'pet_category_id' => $petCategory->id,
            'name' => 'boubou',
        ]);

        $collection = ModulePetsViewHelper::dto($contact, $pet);

        $this->assertEquals(
            [
                'id' => $pet->id,
                'name' => 'boubou',
                'pet_category' => [
                    'id' => $petCategory->id,
                    'name' => $petCategory->name,
                ],
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/pets/'.$pet->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/pets/'.$pet->id,
                ],
            ],
            $collection
        );
    }
}
