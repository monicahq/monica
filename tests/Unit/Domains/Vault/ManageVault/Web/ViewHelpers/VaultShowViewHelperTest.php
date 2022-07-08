<?php

namespace Tests\Unit\Domains\Vault\ManageVault\Web\ViewHelpers;

use App\Models\Avatar;
use App\Models\Contact;
use App\Models\Vault;
use App\Vault\ManageVault\Web\ViewHelpers\VaultShowViewHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use function env;

class VaultShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_latest_updated_contacts(): void
    {
        $vault = Vault::factory()->create();
        $mitchell = Contact::factory()->create([
            'vault_id' => $vault->id,
            'last_updated_at' => Carbon::now()->subDays(1),
        ]);
        $avatar = Avatar::factory()->create([
            'contact_id' => $mitchell->id,
        ]);
        $mitchell->avatar_id = $avatar->id;
        $mitchell->save();
        $john = Contact::factory()->create([
            'vault_id' => $vault->id,
            'last_updated_at' => Carbon::now()->subDays(2),
        ]);
        $avatar = Avatar::factory()->create([
            'contact_id' => $john->id,
        ]);
        $john->avatar_id = $avatar->id;
        $john->save();

        $collection = VaultShowViewHelper::lastUpdatedContacts($vault);
        $this->assertEquals(
            [
                0 => [
                    'id' => $mitchell->id,
                    'name' => $mitchell->name,
                    'avatar' => '123',
                    'url' => [
                        'show' => env('APP_URL') . '/vaults/' . $vault->id . '/contacts/' . $mitchell->id,
                    ],
                ],
                1 => [
                    'id' => $john->id,
                    'name' => $john->name,
                    'avatar' => '123',
                    'url' => [
                        'show' => env('APP_URL') . '/vaults/' . $vault->id . '/contacts/' . $john->id,
                    ],
                ],
            ],
            $collection->toArray()
        );
    }
}
