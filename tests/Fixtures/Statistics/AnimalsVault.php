<?php

declare(strict_types=1);

namespace Tests\Fixtures\Statistics;

use App\Enums\FieldTypeEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;

/**
 * The Animals example of specs/161-template-statistics: a Family vault with 11 animals on Pet v3,
 * 3 on Pet v2, 4 on Horse v1, none on Bird, and 2 archived on Pet v3.
 */
class AnimalsVault
{
    public User $user;

    public Vault $vault;

    public EntityType $animals;

    public Template $pet;

    public Template $horse;

    public Template $bird;

    public StructureVersion $petV2;

    public StructureVersion $petV3;

    public StructureVersion $horseV1;

    public StructureVersion $birdV1;

    /** @var array<string, Field> */
    public array $fields = [];

    /** @var array<string, Entity> */
    public array $horses = [];

    public Reminder $farrierVisit;

    public static function build(?User $user = null): self
    {
        $fixture = new self;
        $fixture->user = $user ?? User::factory()->create();
        $fixture->vault = Vault::factory()->create(['account_id' => $fixture->user->account_id, 'name' => 'Family']);
        $fixture->animals = EntityType::factory()->create(['account_id' => $fixture->user->account_id]);

        $fixture->buildTemplates();
        $fixture->buildRecords();

        return $fixture;
    }

    private function buildTemplates(): void
    {
        $this->pet = $this->template('Pet', 1);
        $this->horse = $this->template('Horse', 2);
        $this->bird = $this->template('Bird', 3);

        $identification = Section::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Identification']);
        $care = Section::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Care']);
        $stable = Section::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Stable']);

        $identificationV1 = $this->sectionVersion($identification, 1, ['birth_date_v2' => ['birth_date', FieldTypeEnum::Date]]);
        $identificationV2 = $this->sectionVersion($identification, 2, [
            'birth_date' => ['birth_date', FieldTypeEnum::Date],
            'microchip' => ['microchip', FieldTypeEnum::Text],
        ]);
        $careV1 = $this->sectionVersion($care, 1, ['veterinarian' => ['veterinarian', FieldTypeEnum::Text]]);
        $stableV1 = $this->sectionVersion($stable, 1, [
            'stable_name' => ['stable_name', FieldTypeEnum::Text],
            'farrier_visit' => ['farrier_visit', FieldTypeEnum::RecurringDate],
        ]);

        $this->petV2 = $this->version($this->pet, 2, [$identificationV1, $careV1]);
        $this->petV3 = $this->version($this->pet, 3, [$identificationV2, $careV1]);
        $this->horseV1 = $this->version($this->horse, 1, [$stableV1]);
        $this->birdV1 = $this->version($this->bird, 1, []);

        $this->pet->structure->update(['published_version_id' => $this->petV3->id]);
        $this->horse->structure->update(['published_version_id' => $this->horseV1->id]);
        $this->bird->structure->update(['published_version_id' => $this->birdV1->id]);

        VaultDefaultTemplate::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'applies_to' => StructureAppliesToEnum::Entity,
            'entity_type_id' => $this->animals->id,
            'structure_version_id' => $this->petV3->id,
        ]);
    }

    private function buildRecords(): void
    {
        foreach (range(1, 11) as $number) {
            $animal = $this->animal("Pet three {$number}", $this->petV3);

            if ($number <= 9) {
                $this->value($animal, $this->fields['birth_date'], ['value_date' => '2019-05-03']);
            }

            if ($number <= 5) {
                $this->value($animal, $this->fields['microchip'], ['value_string' => "250{$number}"]);
            }

            if ($number <= 5) {
                $this->value($animal, $this->fields['veterinarian'], ['value_string' => 'Dr Bob']);
                $this->value($animal, $this->fields['veterinarian'], ['value_string' => 'Dr Vance']);
            }
        }

        foreach (['Pepper', 'Ziggy', 'Nougat'] as $name) {
            $animal = $this->animal($name, $this->petV2);
            $this->value($animal, $this->fields['birth_date_v2'], ['value_date' => '2016-08-12']);
            $this->value($animal, $this->fields['veterinarian'], ['value_string' => 'Dr Bob']);
        }

        $this->value($this->animal('Biscuit', $this->petV3, archived: true), $this->fields['microchip'], ['value_string' => '999']);
        $this->animal('Olive', $this->petV3, archived: true);

        foreach (['Comète', 'Tornado', 'Ulysse', 'Bijou'] as $name) {
            $this->horses[$name] = $this->animal($name, $this->horseV1);
        }

        $this->value($this->horses['Comète'], $this->fields['stable_name'], ['value_string' => 'Les Écuries']);
        $this->value($this->horses['Tornado'], $this->fields['stable_name'], ['value_string' => 'Le Haras']);
        $farrier = $this->value($this->horses['Comète'], $this->fields['farrier_visit'], ['value_date' => '2020-04-02']);

        $this->farrierVisit = Reminder::factory()->for($farrier, 'fieldValue')->create([
            'remindable_type' => Entity::class,
            'name' => 'Farrier visit',
        ]);
    }

    private function template(string $name, int $position): Template
    {
        return Template::factory()->forEntity($this->animals)->create([
            'account_id' => $this->user->account_id,
            'name' => $name,
            'position' => $position,
        ]);
    }

    /** @param array<string, array{0: string, 1: FieldTypeEnum}> $fields */
    private function sectionVersion(Section $section, int $number, array $fields): SectionVersion
    {
        $sectionVersion = SectionVersion::factory()->create([
            'account_id' => $this->user->account_id,
            'section_id' => $section->id,
            'version_number' => $number,
        ]);

        $position = 1;

        foreach ($fields as $name => [$key, $type]) {
            $this->fields[$name] = Field::factory()->create([
                'account_id' => $this->user->account_id,
                'section_version_id' => $sectionVersion->id,
                'key' => $key,
                'label' => ucfirst(str_replace('_', ' ', $key)),
                'type' => $type,
                'position' => $position++,
            ]);
        }

        $sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        return $sectionVersion;
    }

    /** @param list<SectionVersion> $sectionVersions */
    private function version(Template $template, int $number, array $sectionVersions): StructureVersion
    {
        $version = StructureVersion::factory()->create([
            'account_id' => $this->user->account_id,
            'structure_id' => $template->structure->id,
            'version_number' => $number,
        ]);

        foreach ($sectionVersions as $position => $sectionVersion) {
            StructureVersionSection::factory()->create([
                'account_id' => $this->user->account_id,
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $version->pages->first()->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position + 1,
            ]);
        }

        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        return $version;
    }

    private function animal(string $name, StructureVersion $version, bool $archived = false): Entity
    {
        return Entity::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'entity_type_id' => $this->animals->id,
            'structure_version_id' => $version->id,
            'name' => $name,
            'is_archived' => $archived,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function value(Entity $entity, Field $field, array $attributes): FieldValue
    {
        return FieldValue::factory()->create([
            'account_id' => $entity->account_id,
            'vault_id' => $entity->vault_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $entity->id,
            'field_id' => $field->id,
            'value_string' => null,
            ...$attributes,
        ]);
    }
}
