<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactShareLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_link_ever_made_including_the_ended_ones(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->create(['label' => 'For the family doctor']);
        ShareLink::factory()->for($contact, 'shareable')->revoked()->create(['label' => 'For the notaire']);

        $this->actingAs($user)
            ->get(route('contacts.shareLinks.index', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertSee('For the family doctor')
            ->assertSee('For the notaire')
            ->assertSee('Revoked')
            ->assertSee('Live');
    }

    #[Test]
    public function it_offers_a_first_link_when_nothing_is_shared(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->get(route('contacts.shareLinks.index', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertSee('Create your first sharing link')
            ->assertSee('You control what each link shows and can turn off access at any time.');
    }

    #[Test]
    public function it_leaves_out_a_link_onto_somebody_else(): void
    {
        $user = User::factory()->create();
        $michael = $this->contactOf($user);
        $dwight = $this->contactOn($michael->vault, 'Dwight', 'Schrute');
        ShareLink::factory()->for($dwight, 'shareable')->create(['label' => 'For the beet farm']);

        $this->actingAs($user)
            ->get(route('contacts.shareLinks.index', ['vault' => $michael->vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertDontSee('For the beet farm');
    }

    #[Test]
    public function it_offers_the_form_with_every_section_going_out(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $facts = $this->sectionNamed($contact, 'Facts');

        $this->actingAs($user)
            ->get(route('contacts.shareLinks.new', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertSee('Read only, always')
            ->assertSee('Facts')
            ->assertSee('id="section-'.$facts->id.'"', false)
            ->assertSee('checked', false);
    }

    #[Test]
    public function it_makes_a_link_and_hands_the_address_over_once(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $facts = $this->sectionNamed($contact, 'Facts');
        $doctors = $this->sectionNamed($contact, 'Doctors');
        $work = $this->sectionNamed($contact, 'Where they worked');

        $response = $this->actingAs($user)
            ->post(route('contacts.shareLinks.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                'label' => 'For the family doctor',
                'recipient_hint' => 'Dr Ferrand',
                'expiry' => 'thirty_days',
                'sections' => [$facts->id],
            ]);

        $link = ShareLink::query()->sole();

        $response->assertRedirect(route('contacts.shareLinks.show', [
            'vault' => $contact->vault,
            'contact' => $contact,
            'shareLink' => $link,
        ]));
        $response->assertSessionHas('shareLinkUrl');

        $this->assertDatabaseHas('share_links', [
            'id' => $link->id,
            'label' => 'For the family doctor',
            'recipient_hint' => 'Dr Ferrand',
            'shareable_type' => Contact::class,
            'shareable_id' => $contact->id,
            'created_by_user_id' => $user->id,
        ]);
        $this->assertSame([$doctors->id, $work->id], $link->excluded_section_ids);
        $this->assertFalse($link->includes_sensitive_fields);
        $this->assertNull($link->password_hash);
        $this->assertTrue($link->expires_at->isSameDay(now()->addDays(30)));
    }

    #[Test]
    public function it_holds_back_every_section_when_the_form_names_none(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->post(route('contacts.shareLinks.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                'label' => 'For nobody in particular',
                'expiry' => 'never',
            ]);

        $link = ShareLink::query()->sole();

        $this->assertCount(3, $link->excluded_section_ids);
        $this->assertNull($link->expires_at);
    }

    #[Test]
    public function it_refuses_a_span_it_does_not_offer(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->post(route('contacts.shareLinks.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                'label' => 'For the family doctor',
                'expiry' => 'in a while',
            ])
            ->assertSessionHasErrors('expiry');
    }

    #[Test]
    public function it_refuses_a_link_with_no_name(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->post(route('contacts.shareLinks.create', ['vault' => $contact->vault, 'contact' => $contact]), [
                'expiry' => 'never',
            ])
            ->assertSessionHasErrors('label');
    }

    #[Test]
    public function it_shows_the_address_only_on_the_response_that_minted_it(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['label' => 'For the family doctor']);

        $this->actingAs($user)
            ->get(route('contacts.shareLinks.show', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]))
            ->assertStatus(200)
            ->assertSee('The link is live')
            ->assertSee('The address is not here');
    }

    #[Test]
    public function it_lists_the_visits_a_link_had(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['view_count' => 2]);
        $link->visits()->create([
            'account_id' => $link->account_id,
            'ip_address' => '198.51.100.7',
            'user_agent' => 'Dunder Mifflin Explorer',
            'password_was_required' => false,
            'password_failed' => false,
            'viewed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('contacts.shareLinks.show', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]))
            ->assertStatus(200)
            ->assertSee('Opened 2 times')
            ->assertSee('198.51.100.7')
            ->assertSee('Dunder Mifflin Explorer');
    }

    #[Test]
    public function it_changes_what_a_link_shows(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $facts = $this->sectionNamed($contact, 'Facts');
        $link = ShareLink::factory()->for($contact, 'shareable')->create([
            'label' => 'For the family doctor',
            'excluded_section_ids' => [],
        ]);

        $this->actingAs($user)
            ->put(route('contacts.shareLinks.update', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]), [
                'label' => 'For the family doctor',
                'expiry' => 'seven_days',
                'includes_sensitive_fields' => '1',
                'sections' => [$facts->id],
            ])
            ->assertRedirect(route('contacts.shareLinks.show', [
                'vault' => $contact->vault,
                'contact' => $contact,
                'shareLink' => $link,
            ]))
            ->assertSessionHas('status');

        $link->refresh();

        $this->assertTrue($link->includes_sensitive_fields);
        $this->assertCount(2, $link->excluded_section_ids);
        $this->assertNotContains($facts->id, $link->excluded_section_ids);
    }

    #[Test]
    public function it_keeps_the_password_when_the_form_says_nothing_about_it(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create([
            'label' => 'For the family doctor',
            'password_hash' => Hash::make('bears beets battlestar'),
        ]);

        $this->actingAs($user)
            ->put(route('contacts.shareLinks.update', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]), [
                'label' => 'For the family doctor, renamed',
                'expiry' => 'keep',
            ]);

        $link->refresh();

        $this->assertTrue($link->isProtectedByAPassword());
        $this->assertSame('For the family doctor, renamed', $link->label);
    }

    #[Test]
    public function it_stops_asking_for_a_password_when_the_form_says_to(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create([
            'password_hash' => Hash::make('bears beets battlestar'),
        ]);

        $this->actingAs($user)
            ->put(route('contacts.shareLinks.update', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]), [
                'label' => 'For the family doctor',
                'expiry' => 'keep',
                'removes_password' => '1',
            ]);

        $this->assertFalse($link->refresh()->isProtectedByAPassword());
    }

    #[Test]
    public function it_keeps_the_end_date_a_link_already_had(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $ends = now()->addDays(4)->startOfSecond();
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['expires_at' => $ends]);

        $this->actingAs($user)
            ->put(route('contacts.shareLinks.update', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]), [
                'label' => 'For the family doctor',
                'expiry' => 'keep',
            ]);

        $this->assertTrue($link->refresh()->expires_at->equalTo($ends));
    }

    #[Test]
    public function it_leaves_the_inclusions_the_form_does_not_ask_about_alone(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create([
            'includes_files' => true,
            'includes_relationships' => true,
            'excluded_life_event_ids' => [42],
        ]);

        $this->actingAs($user)
            ->put(route('contacts.shareLinks.update', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]), [
                'label' => 'For the family doctor',
                'expiry' => 'never',
            ]);

        $link->refresh();

        $this->assertTrue($link->includes_files);
        $this->assertTrue($link->includes_relationships);
        $this->assertSame([42], $link->excluded_life_event_ids);
    }

    #[Test]
    public function it_refuses_to_edit_a_revoked_link(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->revoked()->create();

        $this->actingAs($user)
            ->get(route('contacts.shareLinks.edit', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]))
            ->assertRedirect(route('contacts.shareLinks.index', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function it_refuses_to_change_a_revoked_link(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->revoked()->create(['label' => 'For the notaire']);

        $this->actingAs($user)
            ->put(route('contacts.shareLinks.update', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]), [
                'label' => 'Brought back',
                'expiry' => 'never',
            ])
            ->assertRedirect(route('contacts.shareLinks.index', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertSessionHas('error');

        $this->assertSame('For the notaire', $link->refresh()->label);
    }

    #[Test]
    public function it_revokes_a_link_and_keeps_its_row(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['view_count' => 3]);

        $this->actingAs($user)
            ->delete(route('contacts.shareLinks.destroy', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]))
            ->assertRedirect(route('contacts.shareLinks.index', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertSessionHas('status');

        $link->refresh();

        $this->assertNotNull($link->revoked_at);
        $this->assertSame(3, $link->view_count);
        $this->assertDatabaseHas('share_links', ['id' => $link->id]);
    }

    #[Test]
    public function it_refuses_to_revoke_twice(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->revoked()->create();

        $this->actingAs($user)
            ->delete(route('contacts.shareLinks.destroy', ['vault' => $contact->vault, 'contact' => $contact, 'shareLink' => $link]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function it_refuses_a_link_that_is_onto_another_contact(): void
    {
        $user = User::factory()->create();
        $michael = $this->contactOf($user);
        $dwight = $this->contactOn($michael->vault, 'Dwight', 'Schrute');
        $link = ShareLink::factory()->for($dwight, 'shareable')->create();

        $this->actingAs($user)
            ->get(route('contacts.shareLinks.show', ['vault' => $michael->vault, 'contact' => $michael, 'shareLink' => $link]))
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_somebody_in_another_account(): void
    {
        $contact = $this->contactOf(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('contacts.shareLinks.index', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_somebody_in_another_vault(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('contacts.shareLinks.index', ['vault' => $elsewhere, 'contact' => $contact]))
            ->assertNotFound();
    }

    #[Test]
    public function it_offers_the_way_in_from_the_record(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertSee(route('contacts.shareLinks.index', ['vault' => $contact->vault, 'contact' => $contact]), false)
            ->assertSee('Share');
    }

    #[Test]
    public function the_record_says_how_many_links_are_open_onto_it(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->create();
        ShareLink::factory()->for($contact, 'shareable')->revoked()->create();

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertStatus(200)
            ->assertSee('Shared by one link');
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return $this->contactOn($vault, 'Michael', 'Scott');
    }

    private function contactOn(Vault $vault, string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $this->versionWithTwoPages($vault)->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function versionWithTwoPages(Vault $vault): StructureVersion
    {
        $template = Template::factory()->create(['account_id' => $vault->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->page($version, 'Overview', ['Facts' => 'Born', 'Doctors' => 'Doctor']);
        $this->page($version, 'Work', ['Where they worked' => 'Employer']);

        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
    }

    /** @param  array<string, string>  $sections */
    private function page(StructureVersion $version, string $label, array $sections): StructureVersionPage
    {
        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => Str::slug($label),
            'label' => $label,
        ]);

        $position = 1;

        foreach ($sections as $sectionName => $fieldLabel) {
            $section = Section::factory()->create(['account_id' => $version->account_id, 'name' => $sectionName]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => $fieldLabel]);

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position++,
            ]);
        }

        return $page;
    }

    private function sectionNamed(Contact $contact, string $name): StructureVersionSection
    {
        return $contact->structureVersion->assembly
            ->first(fn (StructureVersionSection $row): bool => $row->sectionVersion->section->name === $name);
    }
}
