<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmptyStateTest extends TestCase
{
    #[Test]
    public function it_says_what_will_land_here_rather_than_that_nothing_has(): void
    {
        $rendered = $this->blade(
            '<x-empty-state :title="__(\'Nothing has happened yet\')">It is written down here as soon as it does.</x-empty-state>',
        );

        $rendered->assertSee('Nothing has happened yet', escape: false);
        $rendered->assertSee('It is written down here as soon as it does.', escape: false);
    }

    #[Test]
    public function it_may_carry_the_one_thing_to_do_about_the_list_being_empty(): void
    {
        $this->blade('<x-empty-state>Nothing yet.<x-slot:action><button type="button">Make one</button></x-slot:action></x-empty-state>')
            ->assertSee('Make one', escape: false);
    }
}
