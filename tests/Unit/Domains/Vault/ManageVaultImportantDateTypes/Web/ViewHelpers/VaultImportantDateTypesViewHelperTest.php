<?php

namespace Tests\Unit\Domains\Vault\ManageVaultImportantDateTypes\Web\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\Vault;
use App\Models\ContactImportantDateType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Vault\ManageVaultImportantDateTypes\Web\ViewHelpers\VaultImportantDateTypesViewHelper;

class VaultImportantDateTypesViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_information_necessary_to_load_the_view(): void
    {
        $vault = Vault::factory()->create();
        $date = ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $collection = VaultImportantDateTypesViewHelper::data($vault);
        $this->assertEquals(
            1,
            $collection->count()
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $vault = Vault::factory()->create();
        $type = ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $array = VaultImportantDateTypesViewHelper::dto($type, $vault);
        $this->assertEquals(
            [
                'id' => $type->id,
                'label' => $type->label,
                'internal_type' => null,
                'can_be_deleted' => true,
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/contactImportantDateTypes/'.$type->id,
                    'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/settings/contactImportantDateTypes/'.$type->id,
                ],
            ],
            $array
        );
    }
}
