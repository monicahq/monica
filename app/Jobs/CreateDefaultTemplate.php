<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateVault;
use App\Enums\DefaultFieldEnum;
use App\Enums\DefaultSectionEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\ValueListSnapshot;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Give a fresh account the template it starts with, so the first contact
 * somebody writes down has somewhere to go.
 *
 * This is a job rather than part of creating the account, because it writes a
 * couple of dozen rows nobody is looking at yet. Whoever just signed up is waiting on a
 * page, and the first thing they see is not a contact form. The account, the
 * user, the vault and the value lists are written before the request comes
 * back; this arrives a moment later.
 *
 * It is published straight away rather than left as a draft. A draft is a
 * template nothing can be created from, and an account whose only template has
 * to be published before it does anything is an account that starts with
 * homework.
 *
 * Nothing here is protected. The template, its sections and every field are
 * ordinary rows from the moment they are written: they can be renamed,
 * reordered, added to or thrown away, and changing any of them means a new
 * version exactly as it does for a template somebody built themselves.
 *
 * The names are not translated here. They are stored as translation keys and
 * read in the language of whoever is looking, which is what keeps a template
 * seeded in English readable to somebody who later switches to French.
 *
 * The value lists have to exist before this runs, since two of the fields read
 * their choices from them.
 *
 * One entry goes in the log for the whole seeding rather than one per section,
 * since this happens once and nobody asked for it line by line.
 */
class CreateDefaultTemplate implements ShouldQueue
{
    use Queueable;

    private Template $template;

    private TemplateVersion $version;

    /**
     * The id of each of the account's value lists, by key.
     *
     * @var array<string, int>
     */
    private array $valueLists;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        $this->readValueLists();

        DB::transaction(function (): void {
            $this->createTemplate();
            $this->createPages();
            $this->publish();
            $this->seedTheVaultDefaults();
        });

        $this->log();
    }

    private function readValueLists(): void
    {
        $this->valueLists = ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('id', 'key')
            ->all();
    }

    private function createTemplate(): void
    {
        $supplied = DefaultTemplateEnum::BasicContact;

        $this->template = Template::query()->create([
            'account_id' => $this->user->account_id,
            'applies_to' => $supplied->appliesTo(),
            'entity_type_id' => null,
            'name' => null,
            'name_translation_key' => $supplied->label(),
            'description' => null,
            'slug' => $supplied->value,
            'is_system' => true,
            'is_active' => true,
            'published_version_id' => null,
            'locale' => config('app.locale'),
            'position' => 1,
        ]);

        $this->version = TemplateVersion::query()->create([
            'account_id' => $this->user->account_id,
            'template_id' => $this->template->id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Draft,
            'change_note' => null,
            'published_at' => null,
            'retired_at' => null,
            'previous_version_id' => null,
            'record_count' => 0,
        ]);
    }

    /**
     * Lay the template out: a page per tab, and on each of them the sections it
     * is read in the order of.
     *
     * The positions of the sections start again on every page, since that is
     * what a position on a page means. Nothing here is protected: every page,
     * every section and every field is the user's to rename, reorder or throw
     * away from the first minute.
     */
    private function createPages(): void
    {
        $pagePosition = 1;

        foreach (DefaultTemplateEnum::BasicContact->pages() as $pageCase) {
            $page = TemplateVersionPage::query()->create([
                'account_id' => $this->user->account_id,
                'template_version_id' => $this->version->id,
                'key' => $pageCase->value,
                'label' => null,
                'label_translation_key' => $pageCase->label(),
                'position' => $pagePosition,
            ]);

            $sectionPosition = 1;

            foreach ($pageCase->sections() as $case) {
                $sectionVersion = $this->createSection($case);

                TemplateVersionSection::query()->create([
                    'account_id' => $this->user->account_id,
                    'template_version_id' => $this->version->id,
                    'template_version_page_id' => $page->id,
                    'section_version_id' => $sectionVersion->id,
                    'position' => $sectionPosition,
                    'title_override' => null,
                    'is_collapsed_by_default' => false,
                ]);

                $sectionVersion->increment('usage_count');
                $sectionPosition++;
            }

            $pagePosition++;
        }
    }

    /**
     * Build one section, fill it and freeze it. The fields go in while the
     * version is still a draft, since a published one refuses them.
     */
    private function createSection(DefaultSectionEnum $case): SectionVersion
    {
        $section = Section::query()->create([
            'account_id' => $this->user->account_id,
            'name' => null,
            'name_translation_key' => $case->label(),
            'description' => null,
            'slug' => $case->value,
            'icon' => null,
            'is_system' => true,
            'is_active' => true,
            'published_version_id' => null,
            'locale' => config('app.locale'),
        ]);

        $version = SectionVersion::query()->create([
            'account_id' => $this->user->account_id,
            'section_id' => $section->id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Draft,
            'change_note' => null,
            'resolved_value_lists' => null,
            'published_at' => null,
            'retired_at' => null,
            'previous_version_id' => null,
            'usage_count' => 0,
        ]);

        $this->createFields($version, $case);

        $version->status = VersionStatusEnum::Published;
        $version->resolved_value_lists = ValueListSnapshot::of($version);
        $version->published_at = now();
        $version->save();

        $section->published_version_id = $version->id;
        $section->save();

        return $version;
    }

    private function createFields(SectionVersion $version, DefaultSectionEnum $case): void
    {
        $position = 1;

        foreach ($case->fields() as $field) {
            $version->fields()->create([
                'account_id' => $this->user->account_id,
                'type' => $field->type(),
                'key' => $field->value,
                'label' => null,
                'label_translation_key' => $field->label(),
                'help_text' => null,
                'placeholder' => null,
                'position' => $position,
                'is_required' => false,
                'is_sensitive' => false,
                'is_highlighted' => $field->isHighlighted(),
                'is_unique' => false,
                'value_list_id' => $this->valueListId($field),
                'default_value' => null,
                'options' => null,
                'validation' => null,
                'is_repeatable' => false,
                'repeat_min' => null,
                'repeat_max' => null,
                'parent_field_id' => null,
                'is_reminder_source' => $field->isReminderSource(),
                'relation_target' => null,
                'relation_entity_type_id' => null,
            ]);

            $position++;
        }
    }

    private function valueListId(DefaultFieldEnum $field): ?int
    {
        $list = $field->valueList();

        if (! $list instanceof ValueListEnum) {
            return null;
        }

        return $this->valueLists[$list->value] ?? null;
    }

    private function publish(): void
    {
        $this->version->status = VersionStatusEnum::Published;
        $this->version->published_at = now();
        $this->version->save();

        $this->template->published_version_id = $this->version->id;
        $this->template->save();
    }

    /**
     * Point every vault of the account at the version that was just published.
     * The vaults of a fresh account are made before this job runs, so nothing
     * else can have given them a default; {@see CreateVault} gives
     * one to every vault opened afterwards.
     */
    private function seedTheVaultDefaults(): void
    {
        Vault::query()
            ->where('account_id', $this->user->account_id)
            ->each(function (Vault $vault): void {
                VaultDefaultTemplate::query()->updateOrCreate(
                    [
                        'vault_id' => $vault->id,
                        'applies_to' => TemplateAppliesToEnum::Contact,
                        'entity_type_id' => null,
                    ],
                    [
                        'account_id' => $this->user->account_id,
                        'template_version_id' => $this->version->id,
                    ],
                );
            });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::DefaultTemplateSeeded,
            parameters: ['name' => $this->template->name],
            loggable: $this->template,
        )->onQueue('low');
    }
}
