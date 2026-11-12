<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_passkey(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Untitled']);

        $response = $this->withConfirmedPassword()->actingAs($user)->put(route('settings.passkeys.update', $passkey->id), [
            'name' => 'Michael MacBook',
        ]);

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status');
        $this->assertEquals('Michael MacBook', $passkey->refresh()->name);
    }

    #[Test]
    public function it_writes_a_rename_down_without_sending_an_email(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);

        $this->withConfirmedPassword()->actingAs($user)->put(route('settings.passkeys.update', $passkey->id), ['name' => 'Renamed']);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasskeyRenamed,
        );
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_refuses_a_blank_name_and_keeps_the_old_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Michael MacBook']);

        $response = $this->withConfirmedPassword()->actingAs($user)->put(route('settings.passkeys.update', $passkey->id), ['name' => '  ']);

        $response->assertSessionHasErrors('name', errorBag: "passkey{$passkey->id}");
        $this->assertEquals('Michael MacBook', $passkey->refresh()->name);
    }

    #[Test]
    public function it_says_what_still_signs_them_in_after_a_removal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $going = Passkey::factory()->create(['user_id' => $user->id]);
        Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->withConfirmedPassword()->actingAs($user)->delete(route('passkey.destroy', $going->id));

        $response->assertSessionHas('status_description');
    }

    #[Test]
    public function it_says_the_password_still_works_when_the_last_passkey_goes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->withConfirmedPassword()->actingAs($user)->delete(route('passkey.destroy', $passkey->id));

        $response->assertSessionHas('status_description');
    }

    #[Test]
    public function it_warns_a_passwordless_account_that_only_the_emailed_link_is_left(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => null]);
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->withConfirmedPassword()->actingAs($user)->delete(route('passkey.destroy', $passkey->id));

        $response->assertSessionHas('status_description');
    }

    #[Test]
    public function it_does_not_find_a_passkey_belonging_to_another_account_when_renaming(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $somebodyElse = Passkey::factory()->create(['name' => 'Not yours']);

        $response = $this->withConfirmedPassword()->actingAs($user)->put(route('settings.passkeys.update', $somebodyElse->id), [
            'name' => 'Mine now',
        ]);

        $response->assertStatus(404);
        $this->assertEquals('Not yours', $somebodyElse->refresh()->name);
    }

    #[Test]
    public function it_does_not_find_a_passkey_belonging_to_another_account_when_removing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $somebodyElse = Passkey::factory()->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->delete(route('passkey.destroy', $somebodyElse->id));

        $response->assertStatus(404);
        $this->assertModelExists($somebodyElse);
    }
}
