<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Vaults;

use App\Enums\RelationshipTypeEnum;
use App\Models\Contact;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_the_form_a_payload_a_browser_can_read(): void
    {
        $payload = $this->payload($this->form()->getContent());

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('types', $payload);
        $this->assertArrayHasKey('copy', $payload);
        $this->assertArrayHasKey('contactName', $payload);
    }

    #[Test]
    public function it_writes_no_script_of_its_own_into_the_markup(): void
    {
        $this->form()
            ->assertSee('x-data="relationshipForm(', escape: false)
            ->assertDontSee('get hasType()', escape: false)
            ->assertDontSee('.replace(\':role\'', escape: false);
    }

    #[Test]
    public function it_tells_the_form_both_roles_of_every_type_it_offers(): void
    {
        $payload = $this->payload($this->form()->getContent());

        $this->assertCount(1, $payload['types']);
        $this->assertEquals(
            ['roleA' => 'Parent', 'roleB' => 'Child', 'isSymmetric' => false],
            reset($payload['types']),
        );
    }

    #[Test]
    public function it_names_the_contact_the_sentence_will_be_built_around(): void
    {
        $payload = $this->payload($this->form()->getContent());

        $this->assertEquals('Michael Scott', $payload['contactName']);
        $this->assertEquals(':one is :two’s :role.', $payload['copy']['sentence']);
    }

    #[Test]
    public function it_starts_with_nothing_chosen(): void
    {
        $payload = $this->payload($this->form()->getContent());

        $this->assertEquals('', $payload['typeId']);
        $this->assertEquals('', $payload['mode']);
        $this->assertFalse($payload['swapped']);
    }

    #[Test]
    public function it_offers_the_three_ways_of_naming_the_other_side_as_one_group(): void
    {
        $this->form()
            ->assertSee('<fieldset', escape: false)
            ->assertSee('Who holds this role')
            ->assertSee('I do not know their name')
            ->assertSee('I know their name')
            ->assertSee('Choose someone already written down');
    }

    #[Test]
    public function it_never_nests_one_form_inside_another(): void
    {
        $html = $this->form()->getContent();
        $opens = strpos($html, 'id="relationship-form"');
        $closes = strpos($html, '</form>', (int) $opens);

        $this->assertIsInt($opens);
        $this->assertStringNotContainsString('<form', substr($html, (int) $opens, (int) $closes - (int) $opens));
    }

    #[Test]
    public function it_keeps_the_navigation_and_the_way_back_around_it(): void
    {
        $this->form()
            ->assertSee('id="main-nav"', escape: false)
            ->assertSee('aria-label="Breadcrumb"', escape: false);
    }

    #[Test]
    public function it_leaves_the_form_without_reloading_the_whole_page(): void
    {
        $html = $this->form()->getContent();
        $cancel = strrpos($html, 'Cancel');

        $this->assertIsInt($cancel);
        $this->assertStringContainsString('data-turbo="true"', substr($html, (int) $cancel - 400, 400));
    }

    #[Test]
    public function it_announces_the_preview_sentence_as_it_changes(): void
    {
        $this->form()->assertSee('aria-live="polite"', escape: false);
    }

    #[Test]
    public function it_offers_to_write_the_other_person_down_as_a_contact(): void
    {
        $this->form()
            ->assertSee('Create a contact record for this person')
            ->assertSee('name="creates_record"', escape: false);
    }

    #[Test]
    public function it_says_so_when_the_account_has_no_type_to_offer(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->actingAs($user)
            ->get(route('contacts.relationships.new', ['vault' => $vault, 'contact' => $contact]))
            ->assertSee('There is no relationship type to choose. Add one in your settings first.')
            ->assertDontSee('Who holds this role');
    }

    /** @return array<string, mixed> */
    private function payload(string $html): array
    {
        $this->assertEquals(1, preg_match("/x-data=\"relationshipForm\(JSON\.parse\('(.*?)'\)\)\"/s", $html, $found));

        $json = preg_replace_callback(
            '/\\\\u([0-9a-fA-F]{4})/',
            fn (array $escape): string => mb_chr((int) hexdec($escape[1])),
            html_entity_decode($found[1], ENT_QUOTES),
        );

        $decoded = json_decode((string) $json, true);

        $this->assertIsArray($decoded);

        return $decoded;
    }

    private function form(): TestResponse
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $category = RelationshipCategory::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Family',
            'position' => 1,
        ]);

        RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'position' => 1,
        ]);

        return $this->actingAs($user)
            ->get(route('contacts.relationships.new', ['vault' => $vault, 'contact' => $contact]))
            ->assertStatus(200);
    }
}
