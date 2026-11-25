<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\FieldCorrespondence;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldCorrespondenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_field_each_answer_follows_to_without_writing_anything(): void
    {
        $user = User::factory()->create();
        $dwight = $this->contactOf($user);
        $held = Field::factory()->create(['account_id' => $user->account_id, 'key' => 'website', 'type' => FieldTypeEnum::Url]);
        $value = $this->valueOf($dwight, $held, 'https://schrutefarms.com');
        $to = $this->publishedContactVersion($user);
        $carried = $this->fieldCarriedBy($to);
        $carried->update(['key' => 'website', 'type' => FieldTypeEnum::Url]);

        $counterparts = new FieldCorrespondence($to)->counterpartsFor($dwight);

        $this->assertSame([$held->id], array_keys($counterparts));
        $this->assertTrue($carried->is($counterparts[$held->id]));
        $this->assertSame([$carried->id], new FieldCorrespondence($to)->carriedFieldIds());
        $this->assertDatabaseHas('field_values', ['id' => $value->id, 'field_id' => $held->id, 'is_detached' => false]);
        $this->assertEquals($dwight->structure_version_id, $dwight->fresh()->structure_version_id);
    }

    #[Test]
    public function it_names_nothing_for_a_record_whose_answers_all_sit_in_carried_fields(): void
    {
        $user = User::factory()->create();
        $dwight = $this->contactOf($user);
        $to = $this->publishedContactVersion($user);
        $this->valueOf($dwight, $this->fieldCarriedBy($to), 'Assistant to the regional manager');

        $this->assertSame([], new FieldCorrespondence($to)->counterpartsFor($dwight));
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $this->publishedContactVersion($user),
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);
    }

    private function publishedContactVersion(User $user): StructureVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        return StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
    }

    private function valueOf(Contact $contact, Field $field, string $text): FieldValue
    {
        return FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => $text,
        ]);
    }

    private function fieldCarriedBy(StructureVersion $version): Field
    {
        $version->status = VersionStatusEnum::Draft;
        $version->save();

        $sectionVersion = SectionVersion::factory()->create(['account_id' => $version->account_id]);
        $field = Field::factory()->create([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        StructureVersionSection::factory()->create([
            'account_id' => $version->account_id,
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $version->pages->first()->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->save();

        $version->status = VersionStatusEnum::Published;
        $version->save();

        return $field;
    }
}
