<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountFlagActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));
    }

    #[Test]
    public function it_activates_a_flag_for_one_account(): void
    {
        $pam = User::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->post(route('admin.accounts.flags.activation.create', ['account' => $pam->account_id, 'flag' => 'example-capability']));

        $response->assertRedirect(route('admin.accounts.show', ['account' => $pam->account_id]));
        $response->assertSessionHas('status', 'The flag is on for this account.');

        Feature::flushCache();
        $this->assertTrue($pam->account->features()->active('example-capability'));
    }

    #[Test]
    public function it_deactivates_a_flag_for_one_account(): void
    {
        $pam = User::factory()->create();
        $pam->account->features()->activate('example-capability');

        $response = $this->actingAs($this->administrator())
            ->delete(route('admin.accounts.flags.activation.destroy', ['account' => $pam->account_id, 'flag' => 'example-capability']));

        $response->assertRedirect(route('admin.accounts.show', ['account' => $pam->account_id]));
        $response->assertSessionHas('status', 'The flag is off for this account.');

        Feature::flushCache();
        $this->assertFalse($pam->account->features()->active('example-capability'));
    }

    #[Test]
    public function it_answers_not_found_for_a_flag_nobody_declared(): void
    {
        $pam = User::factory()->create();

        $this->actingAs($this->administrator())
            ->post(route('admin.accounts.flags.activation.create', ['account' => $pam->account_id, 'flag' => 'dundie-awards']))
            ->assertNotFound();
    }

    private function administrator(): User
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
