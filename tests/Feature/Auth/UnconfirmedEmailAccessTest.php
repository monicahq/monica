<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnconfirmedEmailAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function everyGatedScreen(): array
    {
        return [
            'the vaults' => ['vaults.index'],
            'the settings' => ['settings.index'],
        ];
    }

    #[Test]
    #[DataProvider('everyGatedScreen')]
    public function it_sends_somebody_who_has_not_confirmed_their_address_to_the_notice(string $name): void
    {
        $michael = User::factory()->unverified()->create();

        $this->actingAs($michael)->get(route($name))->assertRedirect(route('auth.verification.notice'));
    }

    #[Test]
    public function the_notice_itself_stays_reachable(): void
    {
        $michael = User::factory()->unverified()->create();

        $this->actingAs($michael)->get(route('auth.verification.notice'))->assertStatus(200);
    }
}
