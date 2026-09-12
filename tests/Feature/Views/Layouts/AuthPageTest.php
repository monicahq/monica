<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthPageTest extends TestCase
{
    /** @return array{text: string, author: string, source: string} */
    private function quote(): array
    {
        return ['text' => 'Bears. Beets. Battlestar Galactica.', 'author' => 'Jim Halpert', 'source' => 'The Office'];
    }

    #[Test]
    public function it_names_the_screen_once_in_the_title_and_once_on_it(): void
    {
        $this->blade('<x-auth-page title="Welcome back">The card</x-auth-page>')
            ->assertSee('<title>Welcome back</title>', escape: false)
            ->assertSee('text-2xl font-semibold tracking-tight text-ink', escape: false)
            ->assertSee('Welcome back')
            ->assertSee('The card');
    }

    #[Test]
    public function the_line_under_the_title_is_left_out_when_there_is_none(): void
    {
        $this->blade('<x-auth-page title="Welcome back">The card</x-auth-page>')
            ->assertDontSee('text-sm text-body', escape: false);

        $this->blade('<x-auth-page title="Welcome back" description="Sign in to carry on.">The card</x-auth-page>')
            ->assertSee('Sign in to carry on.');
    }

    #[Test]
    public function it_shows_the_quote_only_when_it_is_given_one(): void
    {
        $this->blade('<x-auth-page title="Welcome back" :quote="$quote">The card</x-auth-page>', ['quote' => $this->quote()])
            ->assertSee('Bears. Beets. Battlestar Galactica.')
            ->assertSee('Jim Halpert');

        $this->blade('<x-auth-page title="Welcome back">The card</x-auth-page>')
            ->assertDontSee('Jim Halpert');
    }

    #[Test]
    public function every_screen_a_visitor_sees_offers_the_same_way_to_change_the_theme(): void
    {
        $this->blade('<x-auth-page title="Welcome back">The card</x-auth-page>')
            ->assertSee('$store.theme.toggle()', escape: false);
    }

    #[Test]
    public function it_signs_itself_off_at_the_foot_of_the_column(): void
    {
        $this->blade('<x-auth-page title="Welcome back">The card</x-auth-page>')
            ->assertSee(config('app.name'))
            ->assertSee(__('Open source HR'))
            ->assertSee(now()->format('Y'));
    }
}
