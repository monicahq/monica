<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\DefaultTemplateEnum;
use App\Enums\StructureAppliesToEnum;
use App\Models\Account;
use App\Models\StructureVersion;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_its_versions_newest_first(): void
    {
        $template = Template::factory()->create();
        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);

        $this->assertEquals([2, 1], $template->versions->pluck('version_number')->all());
    }

    #[Test]
    public function it_reads_by_its_translation_key_when_the_product_supplied_it(): void
    {
        $template = Template::factory()->supplied()->create();

        $this->assertNull($template->getRawOriginal('name'));
    }

    #[Test]
    public function it_starts_new_records_on_an_active_template_before_one_turned_off(): void
    {
        $account = Account::factory()->create();
        Template::factory()->published()->inactive()->create(['account_id' => $account->id, 'is_system' => true, 'slug' => DefaultTemplateEnum::BasicContact->value]);
        $dunderMifflin = Template::factory()->published()->create(['account_id' => $account->id]);

        $this->assertTrue(Template::startingPointFor(accountId: $account->id, appliesTo: StructureAppliesToEnum::Contact)->is($dunderMifflin));
    }

    #[Test]
    public function it_starts_new_records_on_a_turned_off_template_when_nothing_else_is_left(): void
    {
        $account = Account::factory()->create();
        $turnedOff = Template::factory()->published()->inactive()->create(['account_id' => $account->id]);

        $this->assertTrue(Template::startingPointFor(accountId: $account->id, appliesTo: StructureAppliesToEnum::Contact)->is($turnedOff));
    }

    #[Test]
    public function it_starts_new_records_on_no_template_without_a_published_version(): void
    {
        $account = Account::factory()->create();
        Template::factory()->create(['account_id' => $account->id]);
        $published = Template::factory()->published()->create(['account_id' => $account->id]);

        $this->assertNull(Template::startingPointFor(accountId: $account->id, appliesTo: StructureAppliesToEnum::Contact, exceptTemplateId: $published->id));
    }
}
