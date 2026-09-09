<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\User;
use App\ViewModels\Vaults\ContactReminderBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactReminderBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_the_panel_every_wording_the_language_needs_rather_than_a_singular_and_a_plural(): void
    {
        app()->setLocale('pl_PL');

        $words = $this->block()->live()['words'];

        $this->assertSame(':n tydzień', $words['units']['weeks']['one']);
        $this->assertSame(':n tygodnie', $words['units']['weeks']['few']);
        $this->assertSame(':n tygodni', $words['units']['weeks']['many']);

        $this->assertSame('co rok', $words['intervals']['yearly']['one']);
        $this->assertSame('co :n lata', $words['intervals']['yearly']['few']);
        $this->assertSame('co :n lat', $words['intervals']['yearly']['many']);
    }

    #[Test]
    public function it_names_the_locale_the_way_the_browser_names_one(): void
    {
        app()->setLocale('pt_BR');

        $this->assertSame('pt-BR', $this->block()->live()['locale']);
    }

    #[Test]
    public function it_hands_the_panel_the_separator_and_the_pattern_a_list_is_joined_by(): void
    {
        app()->setLocale('fr_FR');

        $words = $this->block()->live()['words'];

        $this->assertSame(', ', $words['listSeparator']);
        $this->assertSame(':list et :last', $words['listPattern']);
    }

    #[Test]
    public function it_pads_the_moments_with_the_blanks_the_panel_reveals_one_at_a_time(): void
    {
        $reminder = Reminder::factory()->create();
        $block = $this->block($reminder);

        $held = count($block->moments());

        $this->assertCount($held + $block->blanks(), $block->rows());
        $this->assertSame($held, $block->live()['held']);
    }

    #[Test]
    public function every_blank_moment_offers_a_week_before_the_date(): void
    {
        $rows = $this->block()->rows();
        $blank = end($rows);

        $this->assertSame('before', $blank['direction']);
        $this->assertSame(7, $blank['amount']);
        $this->assertSame('days', $blank['unit']);
        $this->assertFalse($blank['removed']);
    }

    #[Test]
    public function it_says_how_much_of_each_unit_a_moment_may_ask_for(): void
    {
        $maximums = $this->block()->live()['maximums'];

        $this->assertArrayHasKey('days', $maximums);
        $this->assertArrayHasKey('weeks', $maximums);
        $this->assertArrayHasKey('months', $maximums);
        $this->assertGreaterThan(0, $maximums['days']);
    }

    #[Test]
    public function it_names_the_directions_the_units_and_the_cadences_the_controls_offer(): void
    {
        $block = $this->block();

        $this->assertSame(['on', 'before', 'after'], array_keys($block->directions()));
        $this->assertSame(['days', 'weeks', 'months'], array_keys($block->units()));
        $this->assertSame('On the day', $block->directions()['on']);
        $this->assertSame('days', $block->units()['days']);

        $this->assertArrayNotHasKey(ReminderCadenceEnum::Once->value, $block->cadences());
        $this->assertArrayHasKey(ReminderCadenceEnum::Yearly->value, $block->cadences());
    }

    #[Test]
    public function it_joins_the_moments_of_a_saved_rule_with_the_pattern_and_not_with_an_english_and(): void
    {
        app()->setLocale('fr_FR');

        $reminder = Reminder::factory()->create([
            'moments' => [
                ['direction' => 'before', 'amount' => 7, 'unit' => 'days'],
                ['direction' => 'on', 'amount' => null, 'unit' => null],
            ],
        ]);

        $summary = $this->block($reminder)->summary();

        $this->assertStringContainsString(' et ', $summary);
        $this->assertStringNotContainsString(' and ', $summary);
    }

    private function block(?Reminder $reminder = null): ContactReminderBlock
    {
        $user = User::factory()->create();

        return new ContactReminderBlock(
            user: $user,
            contact: Contact::factory()->create(['account_id' => $user->account_id]),
            field: Field::factory()->create(['account_id' => $user->account_id]),
            reminder: $reminder,
        );
    }
}
