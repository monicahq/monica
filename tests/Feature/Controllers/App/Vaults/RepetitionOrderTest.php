<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\DefaultFieldEnum;
use App\Enums\RepetitionOrderEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Models\Contact;
use App\Models\Field;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepetitionOrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_draws_a_blank_after_what_is_there_when_the_repeater_says_nothing(): void
    {
        [$user, $vault, $contact, $repeater] = $this->aContactWithADocument();

        $indices = $this->indicesOn($user, $vault, $contact, $repeater);

        $this->assertGreaterThan(max($indices['existing']), min($indices['blank']));
    }

    #[Test]
    public function it_draws_a_blank_before_what_is_there_when_the_repeater_collects_at_the_front(): void
    {
        [$user, $vault, $contact, $repeater] = $this->aContactWithADocument();

        $this->declare($repeater, RepetitionOrderEnum::NewestFirst);

        $indices = $this->indicesOn($user, $vault, $contact, $repeater);

        $this->assertLessThan(min($indices['existing']), max($indices['blank']));
    }

    #[Test]
    public function it_keeps_the_identity_of_what_is_there_whichever_end_it_draws_the_blank_at(): void
    {
        foreach ([RepetitionOrderEnum::NewestLast, RepetitionOrderEnum::NewestFirst] as $order) {
            [$user, $vault, $contact, $repeater] = $this->aContactWithADocument();

            $this->declare($repeater, $order);

            $held = $contact->fieldValueRows()->where('field_id', $repeater->id)->pluck('id');

            $html = $this->formFor($user, $vault, $contact, $repeater);

            foreach ($held as $id) {
                $this->assertStringContainsString(
                    'value="'.$id.'"',
                    $html,
                    "The repetition {$id} lost its identity when the blank was drawn {$order->value}.",
                );
            }
        }
    }

    #[Test]
    public function it_reads_a_block_in_the_same_order_it_offers_it_as_a_form(): void
    {
        [$user, $vault, $contact, $repeater] = $this->aContactWithADocument();

        $this->declare($repeater, RepetitionOrderEnum::NewestFirst);

        $description = $repeater->children->firstWhere('key', DefaultFieldEnum::Description->value);

        $this->actingAs($user)->put(
            route('contacts.sections.update', [$vault, $contact, $this->sectionOf($repeater)]),
            ['fields' => [$repeater->id => [
                0 => ['values' => [$description->id => 'The newer one']],
                1 => ['id' => $contact->fieldValueRows()->where('field_id', $repeater->id)->value('id'), 'values' => [$description->id => 'The lease']],
            ]]],
        )->assertRedirect();

        $reading = $this->actingAs($user)
            ->get(route('contacts.show', [$vault, $contact, 'page' => $this->pageOf($repeater)]))
            ->getContent();

        $form = $this->formFor($user, $vault, $contact, $repeater);

        foreach ([$reading, $form] as $html) {
            $newer = mb_strpos($html, 'The newer one');
            $older = mb_strpos($html, 'The lease');

            $this->assertNotFalse($newer);
            $this->assertNotFalse($older);
            $this->assertLessThan($older, $newer, 'The newer repetition comes first in both states of the block.');
        }
    }

    #[Test]
    public function it_puts_a_new_document_last_on_the_template_an_account_starts_with(): void
    {
        [$user, $vault, $contact, $repeater] = $this->aContactWithADocument();

        $description = $repeater->children->firstWhere('key', DefaultFieldEnum::Description->value);

        $this->actingAs($user)->put(
            route('contacts.sections.update', [$vault, $contact, $this->sectionOf($repeater)]),
            ['fields' => [$repeater->id => [
                0 => ['id' => $contact->fieldValueRows()->where('field_id', $repeater->id)->value('id'), 'values' => [$description->id => 'The lease']],
                1 => ['values' => [$description->id => 'The insurance']],
            ]]],
        )->assertRedirect();

        $html = $this->formFor($user, $vault, $contact, $repeater);

        $this->assertLessThan(
            mb_strpos($html, 'The insurance'),
            mb_strpos($html, 'The lease'),
            'The document added second comes last.',
        );
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: Field} */
    private function aContactWithADocument(): array
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();

        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'structure_version_id' => Template::query()->where('account_id', $user->account_id)->sole()->structure->published_version_id,
        ]);

        $repeater = Field::query()
            ->where('account_id', $user->account_id)
            ->where('key', DefaultFieldEnum::Document->value)
            ->sole();

        $description = $repeater->children->firstWhere('key', DefaultFieldEnum::Description->value);

        $this->actingAs($user)->put(
            route('contacts.sections.update', [$vault, $contact, $this->sectionOf($repeater)]),
            ['fields' => [$repeater->id => [['values' => [$description->id => 'The lease']]]]],
        )->assertRedirect();

        return [$user, $vault, $contact->refresh(), $repeater];
    }

    private function declare(Field $repeater, RepetitionOrderEnum $order): void
    {
        Field::query()->whereKey($repeater->id)->update(['repetition_order' => $order->value]);

        $repeater->refresh();
    }

    private function sectionOf(Field $field): StructureVersionSection
    {
        return StructureVersionSection::query()
            ->where('account_id', $field->account_id)
            ->where('section_version_id', $field->section_version_id)
            ->sole();
    }

    private function formFor(User $user, Vault $vault, Contact $contact, Field $repeater): string
    {
        $response = $this->actingAs($user)->get(
            route('contacts.sections.edit', [$vault, $contact, $this->sectionOf($repeater)]),
        );

        $response->assertOk();

        return $response->getContent();
    }

    /** @return array{existing: list<int>, blank: list<int>} */
    private function indicesOn(User $user, Vault $vault, Contact $contact, Field $repeater): array
    {
        $html = $this->formFor($user, $vault, $contact, $repeater);

        preg_match_all('/name="fields\['.$repeater->id.'\]\[(\d+)\]\[id\]"/', $html, $existing);
        preg_match_all('/name="fields\['.$repeater->id.'\]\[(\d+)\]\[values\]/', $html, $all);

        $existingIndices = array_values(array_unique(array_map('intval', $existing[1])));
        $blankIndices = array_values(array_diff(array_unique(array_map('intval', $all[1])), $existingIndices));

        $this->assertNotEmpty($existingIndices, 'No repetition the record holds was drawn.');
        $this->assertNotEmpty($blankIndices, 'No blank repetition was drawn.');

        return ['existing' => $existingIndices, 'blank' => $blankIndices];
    }

    private function pageOf(Field $field): string
    {
        return $this->sectionOf($field)->structureVersionPage->key;
    }
}
