<?php

namespace Tests\Unit\Domains\Contact\ManageReligion\Web\ViewHelpers;

use App\Domains\Contact\ManageReligion\Web\ViewHelpers\ModuleReligionViewHelper;
use App\Models\Contact;
use App\Models\Religion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleReligionViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();

        $array = ModuleReligionViewHelper::data($contact);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('religion', $array);
        $this->assertArrayHasKey('religions', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/religion',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_all_the_religions(): void
    {
        $contact = Contact::factory()->create();
        $religion = Religion::factory()->create([
            'account_id' => $contact->vault->account->id,
        ]);

        $collection = ModuleReligionViewHelper::list($contact);
        $this->assertEquals(
            [
                0 => [
                    'id' => $religion->id,
                    'name' => $religion->name,
                    'selected' => false,
                ],
            ],
            $collection->toArray()
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $contact = Contact::factory()->create();
        $religion = Religion::factory()->create([
            'account_id' => $contact->vault->account->id,
        ]);

        $collection = ModuleReligionViewHelper::dto($religion, $contact);
        $this->assertEquals(
            [
                'id' => $religion->id,
                'name' => $religion->name,
                'selected' => false,
            ],
            $collection
        );
    }
}
