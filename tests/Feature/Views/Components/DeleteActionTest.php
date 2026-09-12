<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteActionTest extends TestCase
{
    private const CALL = '<x-delete-action title="Delete the list?" confirm="Delete the list" action="/lists/1" label="Delete"><p>Every value goes with it.</p></x-delete-action>';

    #[Test]
    public function it_always_warns_that_the_action_is_final(): void
    {
        $this->blade(self::CALL)
            ->assertSee('Every value goes with it.', escape: false)
            ->assertSee(__('This cannot be undone.'), escape: false);
    }

    #[Test]
    public function it_asks_before_it_destroys(): void
    {
        $this->blade(self::CALL)
            ->assertSee('role="dialog"', escape: false)
            ->assertSee('aria-modal="true"', escape: false)
            ->assertSee('Delete the list?', escape: false)
            ->assertSee('action="/lists/1"', escape: false);
    }

    #[Test]
    public function the_trigger_opens_the_card_rather_than_submitting_anything(): void
    {
        $this->blade(self::CALL)
            ->assertSee('type="button"', escape: false)
            ->assertSee('open = true', escape: false);
    }

    #[Test]
    public function the_quiet_trigger_only_turns_red_under_the_pointer(): void
    {
        $this->blade(self::CALL)->assertSee('text-error', escape: false);

        $this->blade('<x-delete-action title="T" confirm="C" action="/x" label="Delete" variant="ghost"><p>Why</p></x-delete-action>')
            ->assertSee('hover:text-error', escape: false);
    }
}
