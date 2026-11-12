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

class FlagActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));
    }

    #[Test]
    public function it_activates_a_flag_for_every_account(): void
    {
        $response = $this->actingAs($this->administrator())
            ->post(route('admin.flags.activation.create', ['flag' => 'example-capability']));

        $response->assertRedirect(route('admin.flags.index'));
        $response->assertSessionHas('status');

        Feature::flushCache();
        $this->assertTrue(Feature::for(null)->active('example-capability'));
    }

    #[Test]
    public function it_deactivates_a_flag_for_every_account(): void
    {
        $pam = User::factory()->create();
        $pam->account->features()->activate('example-capability');

        $response = $this->actingAs($this->administrator())
            ->delete(route('admin.flags.activation.destroy', ['flag' => 'example-capability']));

        $response->assertRedirect(route('admin.flags.index'));
        $response->assertSessionHas('status');

        Feature::flushCache();
        $this->assertFalse($pam->account->features()->active('example-capability'));
    }

    #[Test]
    public function it_answers_not_found_for_a_flag_nobody_declared(): void
    {
        $this->actingAs($this->administrator())
            ->post(route('admin.flags.activation.create', ['flag' => 'dundie-awards']))
            ->assertNotFound();
    }

    private function administrator(): User
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);
        $this->session(['auth.password_confirmed_at' => time()]);

        return $michael;
    }
}
