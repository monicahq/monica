<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Enums\AdministratorActionEnum;
use App\Models\Account;
use App\Models\Administrator;
use App\Models\AdministratorLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministratorLogControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_what_administrators_have_done(): void
    {
        $michael = $this->administrator();

        AdministratorLog::factory()->create([
            'user_id' => $michael->id,
            'action' => AdministratorActionEnum::UserDeactivated->value,
            'parameters' => ['name' => 'Todd Packer'],
        ]);

        $response = $this->actingAs($michael)->get(route('admin.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Deactivated Todd Packer', escape: false);
        $response->assertSee('Michael Scott', escape: false);
    }

    #[Test]
    public function it_shows_the_most_recent_thing_first(): void
    {
        $michael = $this->administrator();

        AdministratorLog::factory()->create([
            'user_id' => $michael->id,
            'action' => AdministratorActionEnum::UserDeactivated->value,
            'parameters' => ['name' => 'Todd Packer'],
            'created_at' => now()->subDay(),
        ]);
        AdministratorLog::factory()->create([
            'user_id' => $michael->id,
            'action' => AdministratorActionEnum::UserReactivated->value,
            'parameters' => ['name' => 'Todd Packer'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($michael)->get(route('admin.logs.index'));

        $response->assertSeeInOrder(['Reactivated Todd Packer', 'Deactivated Todd Packer'], escape: false);
    }

    #[Test]
    public function it_narrows_the_record_to_one_administrator(): void
    {
        $michael = $this->administrator();
        $dwight = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);

        AdministratorLog::factory()->create([
            'user_id' => $michael->id,
            'action' => AdministratorActionEnum::UserDeactivated->value,
            'parameters' => ['name' => 'Todd Packer'],
        ]);
        AdministratorLog::factory()->create([
            'user_id' => $dwight->id,
            'action' => AdministratorActionEnum::UserReactivated->value,
            'parameters' => ['name' => 'Todd Packer'],
        ]);

        $response = $this->actingAs($michael)->get(route('admin.logs.index', ['administrator' => $dwight->id]));

        $response->assertSee('Reactivated Todd Packer', escape: false);
        $response->assertDontSee('Deactivated Todd Packer', escape: false);
    }

    #[Test]
    public function it_narrows_the_record_to_one_account(): void
    {
        $michael = $this->administrator();
        $account = Account::factory()->create();

        AdministratorLog::factory()->create([
            'user_id' => $michael->id,
            'account_id' => $account->id,
            'action' => AdministratorActionEnum::LifetimeAccessGranted->value,
            'parameters' => ['name' => 'Dwight Schrute'],
        ]);
        AdministratorLog::factory()->create([
            'user_id' => $michael->id,
            'account_id' => null,
            'action' => AdministratorActionEnum::UserDeactivated->value,
            'parameters' => ['name' => 'Todd Packer'],
        ]);

        $response = $this->actingAs($michael)->get(route('admin.logs.index', ['account' => $account->id]));

        $response->assertSee('Granted lifetime access to the account of Dwight Schrute', escape: false);
        $response->assertDontSee('Deactivated Todd Packer', escape: false);
    }

    #[Test]
    public function it_hands_back_one_page_at_a_time(): void
    {
        $michael = $this->administrator();

        AdministratorLog::factory()->count(26)->create(['user_id' => $michael->id]);

        $response = $this->actingAs($michael)->get(route('admin.logs.index'));

        $response->assertSee('Load more', escape: false);
    }

    #[Test]
    public function it_says_so_plainly_when_nothing_has_been_done_yet(): void
    {
        $michael = $this->administrator();

        $response = $this->actingAs($michael)->get(route('admin.logs.index'));

        $response->assertSee('Nothing has been done yet', escape: false);
    }

    #[Test]
    public function it_shows_everything_support_did_to_an_account(): void
    {
        $michael = $this->administrator();
        $todd = User::factory()->create(['first_name' => 'Todd', 'last_name' => 'Packer']);

        $this->actingAs($michael)->delete(route('admin.accounts.activation.destroy', ['account' => $todd->account_id]));
        $this->actingAs($michael)->post(route('admin.accounts.activation.create', ['account' => $todd->account_id]));
        $this->actingAs($michael)->put(route('admin.accounts.storageLimit.update', ['account' => $todd->account_id]), [
            'storage_limit_in_bytes' => 2048,
        ]);
        $this->actingAs($michael)->post(route('admin.accounts.lifetimeAccess.create', ['account' => $todd->account_id]));
        $this->actingAs($michael)->delete(route('admin.accounts.lifetimeAccess.destroy', ['account' => $todd->account_id]));

        $response = $this->actingAs($michael)->get(route('admin.logs.index', ['account' => $todd->account_id]));

        $response->assertSee('Deactivated Todd Packer', escape: false);
        $response->assertSee('Reactivated Todd Packer', escape: false);
        $response->assertSee('Changed the storage limit of the account of Todd Packer', escape: false);
        $response->assertSee('Granted lifetime access to the account of Todd Packer', escape: false);
        $response->assertSee('Withdrew lifetime access from the account of Todd Packer', escape: false);
        $response->assertSee('Michael Scott', escape: false);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
