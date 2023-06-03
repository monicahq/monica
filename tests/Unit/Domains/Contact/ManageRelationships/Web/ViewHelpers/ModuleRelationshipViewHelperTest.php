<?php

namespace Tests\Unit\Domains\Contact\ManageRelationships\Web\ViewHelpers;

use App\Domains\Contact\ManageRelationships\Web\ViewHelpers\ModuleRelationshipViewHelper;
use App\Models\Contact;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ModuleRelationshipViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $groupType = RelationshipGroupType::factory()->create([
            'account_id' => $user->account_id,
        ]);
        RelationshipType::factory()->create([
            'relationship_group_type_id' => $groupType->id,
        ]);
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $array = ModuleRelationshipViewHelper::data($contact, $user);
        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('relationship_group_types', $array);
        $this->assertArrayHasKey('number_of_defined_relations', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'create' => env('APP_URL').'/vaults/'.$vault->id.'/contacts/'.$contact->id.'/relationships/create',
            ],
            $array['url']
        );
    }
}
