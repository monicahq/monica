<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Docs;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HeaderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_draws_the_navigation_of_the_marketing_site(): void
    {
        config(['monica.marketing_url' => 'https://dundermifflin.test']);

        $view = $this->blade('<x-docs.header />');

        $view->assertSee('aria-label="Main"', false);
        $view->assertSee('href="https://dundermifflin.test/en/pricing/"', false);
        $view->assertSee('href="https://dundermifflin.test/en/blog/"', false);
        $view->assertSee('href="https://dundermifflin.test/en/sales/"', false);
        $view->assertSee('aria-label="25k+ stars, Monica on GitHub"', false);
    }

    #[Test]
    public function it_opens_two_menus_and_marks_the_documentation_as_the_current_section(): void
    {
        $view = $this->blade('<x-docs.header />');

        $this->assertSame(2, substr_count((string) $view, 'x-data="headerMenu"'));
        $view->assertSeeInOrder(['aria-current="true"', 'Docs'], false);
        $view->assertSee('href="'.route('docs.index').'"', false);
        $view->assertSee('href="'.route('docs.portal.index', ['locale' => 'en']).'"', false);
        $view->assertSee('href="'.route('docs.api.index').'"', false);
    }

    #[Test]
    public function it_offers_a_visitor_to_log_in_and_to_sign_up(): void
    {
        config(['monica.registration_enabled' => true]);

        $view = $this->blade('<x-docs.header />');

        $view->assertSee('href="'.route('auth.login.new').'"', false);
        $view->assertSee('href="'.route('auth.register.new').'"', false);
    }

    #[Test]
    public function it_leaves_out_signing_up_while_registration_is_closed(): void
    {
        config(['monica.registration_enabled' => false]);

        $view = $this->blade('<x-docs.header />');

        $view->assertSee('href="'.route('auth.login.new').'"', false);
        $view->assertDontSee('href="'.route('auth.register.new').'"', false);
    }

    #[Test]
    public function it_takes_a_signed_in_reader_back_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $view = $this->blade('<x-docs.header />');

        $view->assertSee('Dashboard');
        $view->assertDontSee('href="'.route('auth.login.new').'"', false);
        $view->assertDontSee('href="'.route('auth.register.new').'"', false);
    }
}
