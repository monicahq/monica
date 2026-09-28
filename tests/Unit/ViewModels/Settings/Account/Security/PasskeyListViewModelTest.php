<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Security;

use App\Models\Passkey;
use App\Models\User;
use App\ViewModels\Settings\Account\Security\PasskeyListViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyListViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_there_are_none_when_there_are_none(): void
    {
        $viewModel = new PasskeyListViewModel(user: User::factory()->create());

        $this->assertEquals([], $viewModel->passkeys());
        $this->assertFalse($viewModel->hasPasskeys());
        $this->assertEquals('None yet', $viewModel->header());
    }

    #[Test]
    public function it_states_that_a_passkey_has_never_been_used(): void
    {
        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id, 'name' => 'MacBook Pro']);

        $passkeys = new PasskeyListViewModel(user: $user)->passkeys();

        $this->assertCount(1, $passkeys);
        $this->assertEquals('MacBook Pro', $passkeys[0]['name']);
        $this->assertNull($passkeys[0]['lastUsedAt']);
        $this->assertNotNull($passkeys[0]['addedAt']);
    }

    #[Test]
    public function it_names_an_unnamed_passkey_after_its_authenticator(): void
    {
        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id, 'name' => '_']);
        $passkey->update(['credential' => ['aaguid' => 'ea9b8d66-4d01-1d21-3ce4-b6b48cb575d4'] + $passkey->credential]);

        $passkeys = new PasskeyListViewModel(user: $user)->passkeys();

        $this->assertEquals('Google Password Manager', $passkeys[0]['name']);
    }

    #[Test]
    public function it_names_an_unnamed_passkey_from_an_unknown_authenticator_this_device(): void
    {
        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id, 'name' => '_']);
        $passkey->update(['credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'] + $passkey->credential]);

        $passkeys = new PasskeyListViewModel(user: $user)->passkeys();

        $this->assertEquals('This device', $passkeys[0]['name']);
    }

    #[Test]
    public function it_puts_the_most_recently_used_first(): void
    {
        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Never used']);
        Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Used a week ago', 'last_used_at' => now()->subWeek()]);
        Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Used today', 'last_used_at' => now()]);

        $names = array_column(new PasskeyListViewModel(user: $user)->passkeys(), 'name');

        $this->assertEquals(['Used today', 'Used a week ago', 'Never used'], $names);
    }

    #[Test]
    public function it_counts_the_passkeys_in_the_header(): void
    {
        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('Active · 1 passkey', new PasskeyListViewModel(user: $user)->header());

        Passkey::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('Active · 2 passkeys', new PasskeyListViewModel(user: $user)->header());
    }

    #[Test]
    public function it_shows_no_passkey_from_another_account(): void
    {
        $user = User::factory()->create();
        Passkey::factory()->create(['name' => 'Somebody else']);

        $this->assertEquals([], new PasskeyListViewModel(user: $user)->passkeys());
    }
}
