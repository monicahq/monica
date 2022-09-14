<?php

namespace Tests\Unit\Helpers;

use App\Helpers\VaultHelper;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VaultHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_the_friendly_type_name(): void
    {
        $this->assertEquals(
            'Viewer',
            VaultHelper::getPermissionFriendlyName(Vault::PERMISSION_VIEW)
        );

        $this->assertEquals(
            'Editor',
            VaultHelper::getPermissionFriendlyName(Vault::PERMISSION_EDIT)
        );

        $this->assertEquals(
            'Manager',
            VaultHelper::getPermissionFriendlyName(Vault::PERMISSION_MANAGE)
        );
    }

    /** @test */
    public function it_returns_the_permission_in_the_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $user->vaults()->sync([
            $vault->id => [
                'permission' => Vault::PERMISSION_VIEW,
                'contact_id' => Contact::factory()->create()->id,
            ],
        ]);

        $this->assertEquals(
            Vault::PERMISSION_VIEW,
            VaultHelper::getPermission($user, $vault)
        );

        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->assertNull(
            VaultHelper::getPermission($user, $vault)
        );
    }
}
