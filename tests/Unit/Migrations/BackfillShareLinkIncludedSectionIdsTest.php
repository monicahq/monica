<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Models\Contact;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillShareLinkIncludedSectionIdsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_every_section_but_the_ones_held_back(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Facts', 'Doctors', 'Where they worked']);
        $contact = $this->contactOn($user, $version);
        $link = ShareLink::factory()->for($contact, 'shareable')->create();
        $this->holdBack($link, [$this->placement($version, 'Doctors')->id]);

        $this->migration()->up();

        $this->assertEqualsCanonicalizing(
            [$this->sectionId('Facts'), $this->sectionId('Where they worked')],
            $link->refresh()->included_section_ids,
        );
    }

    #[Test]
    public function it_still_holds_back_a_section_named_by_a_placement_of_an_earlier_version(): void
    {
        $user = User::factory()->create();
        $oldVersion = $this->version($user, ['Facts', 'Doctors']);
        $newVersion = $this->version($user, ['Facts', 'Doctors']);
        $contact = $this->contactOn($user, $newVersion);
        $link = ShareLink::factory()->for($contact, 'shareable')->create();
        $this->holdBack($link, [$this->placement($oldVersion, 'Doctors')->id]);

        $this->migration()->up();

        $this->assertSame([$this->sectionId('Facts')], $link->refresh()->included_section_ids);
    }

    #[Test]
    public function it_shows_no_section_through_a_link_onto_a_vault(): void
    {
        $user = User::factory()->create();
        $link = ShareLink::factory()->forVault(Vault::factory()->create(['account_id' => $user->account_id]))->create();
        $this->holdBack($link, []);

        $this->migration()->up();

        $this->assertSame([], $link->refresh()->included_section_ids);
    }

    /** @param  list<int>  $placementIds */
    private function holdBack(ShareLink $link, array $placementIds): void
    {
        if (! Schema::hasColumn('share_links', 'excluded_section_ids')) {
            Schema::table('share_links', function (Blueprint $table): void {
                $table->json('excluded_section_ids')->nullable();
            });
        }

        DB::table('share_links')->where('id', $link->id)->update([
            'excluded_section_ids' => json_encode($placementIds),
            'included_section_ids' => json_encode([]),
        ]);
    }

    /** @param  list<string>  $sectionNames */
    private function version(User $user, array $sectionNames): StructureVersion
    {
        $template = Template::query()->where('account_id', $user->account_id)->first()
            ?? Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'version_number' => StructureVersion::query()->where('structure_id', $template->structure->id)->count() + 1,
        ]);
        $version->pages()->delete();

        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => 'overview',
            'label' => 'Overview',
        ]);

        foreach ($sectionNames as $position => $name) {
            $section = Section::query()->where('name', $name)->first()
                ?? Section::factory()->create(['account_id' => $user->account_id, 'name' => $name]);
            $sectionVersion = SectionVersion::query()->where('section_id', $section->id)->first()
                ?? SectionVersion::factory()->create(['section_id' => $section->id]);

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position + 1,
            ]);
        }

        return $version;
    }

    private function contactOn(User $user, StructureVersion $version): Contact
    {
        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'structure_version_id' => $version->id,
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
    }

    private function placement(StructureVersion $version, string $sectionName): StructureVersionSection
    {
        return StructureVersionSection::query()
            ->where('structure_version_id', $version->id)
            ->where('section_version_id', SectionVersion::query()->where('section_id', $this->sectionId($sectionName))->value('id'))
            ->sole();
    }

    private function sectionId(string $name): int
    {
        return Section::query()->where('name', $name)->value('id');
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_13_000001_backfill_share_link_included_section_ids.php');
    }
}
