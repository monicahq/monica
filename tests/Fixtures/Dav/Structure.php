<?php

declare(strict_types=1);

namespace Tests\Fixtures\Dav;

use App\Enums\FieldTypeEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;

class Structure
{
    /**
     * @param  array<int, array{type: FieldTypeEnum, key?: string, label?: string, is_sensitive?: bool}>  $fields
     */
    public static function withFields(User $user, array $fields): TemplateVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $version->pages()->delete();

        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => 'contact',
            'label' => 'Contact',
        ]);

        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        $position = 1;

        foreach ($fields as $field) {
            Field::factory()->create([
                'account_id' => $user->account_id,
                'section_version_id' => $sectionVersion->id,
                'type' => $field['type'],
                'key' => $field['key'] ?? 'field-'.$position,
                'label' => $field['label'] ?? 'Field '.$position,
                'is_sensitive' => $field['is_sensitive'] ?? false,
                'position' => $position,
            ]);

            $position++;
        }

        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'template_version_page_id' => $page->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
    }

    /**
     * @param  array<int, array{type: FieldTypeEnum, key?: string, label?: string, is_sensitive?: bool}>  $fields
     */
    public static function asTheDefaultOf(User $user, Vault $vault, array $fields = []): TemplateVersion
    {
        $version = self::withFields($user, $fields);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => TemplateAppliesToEnum::Contact,
            'template_version_id' => $version->id,
        ]);

        return $version;
    }
}
