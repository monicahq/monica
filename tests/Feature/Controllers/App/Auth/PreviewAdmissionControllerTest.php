<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\PreviewListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewAdmissionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_sign_up_for_the_admitted_address_while_registration_is_closed(): void
    {
        config()->set('monica.registration_enabled', false);

        PreviewListEntry::factory()->admitted('stanley-hudson-token')->create(['email' => 'stanley.hudson@dundermifflin.com']);

        $response = $this->get(route('auth.previewAdmission.show', ['token' => 'stanley-hudson-token']));

        $response->assertOk();
        $response->assertSee('Create my account');
        $response->assertSee('value="stanley.hudson@dundermifflin.com"', false);
        $response->assertSee('name="admission" value="stanley-hudson-token"', false);
    }

    #[Test]
    public function it_answers_not_found_for_a_link_that_no_longer_opens_sign_up(): void
    {
        PreviewListEntry::factory()->admitted('stanley-hudson-token')->registered()->create();

        $this->get(route('auth.previewAdmission.show', ['token' => 'stanley-hudson-token']))->assertNotFound();
        $this->get(route('auth.previewAdmission.show', ['token' => 'phyllis-vance-token']))->assertNotFound();
    }
}
