<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelpTest extends TestCase
{
    #[Test]
    public function it_names_its_mark_after_what_it_explains_and_reports_whether_it_is_open(): void
    {
        $this->blade('<x-help label="Help about the reception desk" title="Who sits at reception?" guide="account.profile">Pam, most days.</x-help>')
            ->assertSee('aria-label="Help about the reception desk"', escape: false)
            ->assertSee(':aria-expanded="open ? \'true\' : \'false\'"', escape: false)
            ->assertSee(':aria-controls="$id(\'help\')"', escape: false)
            ->assertSee('Who sits at reception?', escape: false)
            ->assertSee('Pam, most days.', escape: false);
    }

    #[Test]
    public function it_is_a_group_named_by_its_title_rather_than_a_tooltip(): void
    {
        $this->blade('<x-help label="Help" title="Who sits at reception?" guide="account.profile">Pam.</x-help>')
            ->assertSee('role="group"', escape: false)
            ->assertSee(':aria-labelledby="$id(\'help-title\')"', escape: false)
            ->assertSee(':id="$id(\'help-title\')"', escape: false)
            ->assertDontSee('role="tooltip"', escape: false);
    }

    #[Test]
    public function it_opens_the_guide_in_a_new_tab_and_says_so(): void
    {
        $this->blade('<x-help label="Help" title="Who sits at reception?" guide="account.profile">Pam.</x-help>')
            ->assertSee('Read the guide', escape: false)
            ->assertSee('href="'.route('docs.portal.show', ['locale' => 'en', 'section' => 'your-account-and-security', 'slug' => 'your-profile']).'"', escape: false)
            ->assertSee('target="_blank"', escape: false)
            ->assertSee('(opens in a new tab)', escape: false);
    }

    #[Test]
    public function it_opens_the_guide_at_the_section_it_names(): void
    {
        $this->blade('<x-help label="Help" title="Who?" guide="account.profile#your-email-address">Pam.</x-help>')
            ->assertSee('href="'.route('docs.portal.show', ['locale' => 'en', 'section' => 'your-account-and-security', 'slug' => 'your-profile']).'#your-email-address"', escape: false);
    }

    #[Test]
    public function it_shows_an_example_only_when_given_one(): void
    {
        $this->blade('<x-help label="Help" title="Who?" guide="account.profile">Pam.<x-slot:example>Dwight covers when Pam is out.</x-slot:example></x-help>')
            ->assertSee('For example', escape: false)
            ->assertSee('Dwight covers when Pam is out.', escape: false);

        $this->blade('<x-help label="Help" title="Who?" guide="account.profile">Pam.</x-help>')
            ->assertDontSee('For example', escape: false);
    }

    #[Test]
    public function it_still_explains_when_its_guide_does_not_exist(): void
    {
        $this->blade('<x-help label="Help" title="Who?" guide="dunder.mifflin">Pam.</x-help>')
            ->assertSee('Pam.', escape: false)
            ->assertDontSee('Read the guide', escape: false);
    }
}
