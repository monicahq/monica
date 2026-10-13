<?php

declare(strict_types=1);

namespace Tests\Feature\Views\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlagsTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::forgetDrivers();
        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));
    }

    #[Test]
    public function it_places_the_credential_card_with_the_wording_of_the_admin_space(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('admin.flags.index'));

        $response->assertSee('@confirm-password.window', false);
        $response->assertSee('What you are about to change decides how this instance behaves for everybody, so we ask for a credential first.');
        $response->assertDontSee('What you are about to change decides how you sign in');
    }

    #[Test]
    public function it_asks_for_a_credential_after_the_warning_before_activating_a_flag_for_every_account(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('admin.flags.index'));

        $response->assertSee('Activate example-capability for every account?');
        $response->assertSee("\$dispatch('confirm-password', { form: \$el.form })", false);
    }

    #[Test]
    public function it_asks_for_a_credential_after_the_warning_before_deactivating_a_flag_for_every_account(): void
    {
        Feature::for(null)->activate('example-capability');
        Feature::flushCache();

        $response = $this->actingAs($this->administrator())->get(route('admin.flags.index'));

        $response->assertSee('Deactivate example-capability for every account?');
        $response->assertSee("\$dispatch('confirm-password', { form: \$el.form })", false);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
