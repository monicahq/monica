<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\PreviewListEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewListEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'monica.preview_list_enabled' => true,
            'monica.registration_enabled' => false,
        ]);
    }

    #[Test]
    public function it_shows_the_waiting_list(): void
    {
        $response = $this->get(route('auth.previewList.new'));

        $response->assertOk();
        $response->assertSee('Join the waiting list');
        $response->assertSee('action="'.route('auth.previewList.create').'"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee(route('auth.login.new'));
    }

    #[Test]
    public function it_puts_the_address_on_the_list_and_says_so(): void
    {
        $response = $this->post(route('auth.previewList.create'), [
            'email' => 'kelly.kapoor@dundermifflin.com',
        ]);

        $response->assertRedirect(route('auth.previewList.new'));
        $response->assertSessionHas('status', 'You are on the list. We will write to you when you can create your account.');

        $this->assertDatabaseHas('preview_list_entries', ['email' => 'kelly.kapoor@dundermifflin.com']);
    }

    /** @return array<string, array{string}> */
    public static function knownAddresses(): array
    {
        return [
            'an address already on the list' => ['entry'],
            'an address that holds an account' => ['user'],
        ];
    }

    #[Test]
    #[DataProvider('knownAddresses')]
    public function it_answers_a_known_address_exactly_as_a_new_one(string $known): void
    {
        if ($known === 'entry') {
            PreviewListEntry::factory()->create(['email' => 'kelly.kapoor@dundermifflin.com']);
        } else {
            User::factory()->create(['email' => 'kelly.kapoor@dundermifflin.com']);
        }

        $response = $this->post(route('auth.previewList.create'), [
            'email' => 'kelly.kapoor@dundermifflin.com',
        ]);

        $response->assertRedirect(route('auth.previewList.new'));
        $response->assertSessionHas('status', 'You are on the list. We will write to you when you can create your account.');
        $response->assertSessionHasNoErrors();
    }

    #[Test]
    public function it_refuses_something_that_is_not_an_email_address(): void
    {
        $this->post(route('auth.previewList.create'), ['email' => 'kelly'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('preview_list_entries', 0);
    }

    /** @return array<string, array{bool, bool}> */
    public static function closedCombinations(): array
    {
        return [
            'the list is off and registration closed' => [false, false],
            'the list is on and registration open' => [true, true],
            'the list is off and registration open' => [false, true],
        ];
    }

    #[Test]
    #[DataProvider('closedCombinations')]
    public function it_does_not_exist_unless_the_list_is_on_and_registration_closed(bool $listEnabled, bool $registrationEnabled): void
    {
        config([
            'monica.preview_list_enabled' => $listEnabled,
            'monica.registration_enabled' => $registrationEnabled,
        ]);

        $this->get(route('auth.previewList.new'))->assertNotFound();
        $this->post(route('auth.previewList.create'), ['email' => 'kelly.kapoor@dundermifflin.com'])
            ->assertNotFound();

        $this->assertDatabaseCount('preview_list_entries', 0);
    }
}
