<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Modal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DialogTest extends TestCase
{
    #[Test]
    #[DataProvider('provideTheCardsThatAskBeforeActing')]
    public function it_is_a_dialog_named_by_its_title_that_traps_the_focus(string $template): void
    {
        $rendered = $this->blade($template);

        $rendered->assertSee('role="dialog"', escape: false);
        $rendered->assertSee('aria-modal="true"', escape: false);
        $rendered->assertSee('x-trap.noscroll.inert="open"', escape: false);
        $rendered->assertSee('aria-label="Fire Ryan?"', escape: false);
    }

    /** @return array<string, array<int, string>> */
    public static function provideTheCardsThatAskBeforeActing(): array
    {
        return [
            'warning' => ['<x-modal.warning title="Fire Ryan?" action="/admin/configuration/1"><p>Dunder Mifflin loses its temp.</p><x-slot:trigger><button type="button" @click="open = true">Fire Ryan</button></x-slot:trigger></x-modal.warning>'],
            'form' => ['<x-modal.form title="Fire Ryan?" method="put" action="/admin/configuration/1"><input type="hidden" name="is_active" value="0" /><x-slot:trigger><button type="button" @click="open = true">Fire Ryan</button></x-slot:trigger></x-modal.form>'],
        ];
    }
}
