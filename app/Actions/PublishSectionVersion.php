<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Freeze a draft of a section, so template versions can be built on something that will not
 * move under them.
 *
 * Publishing also takes a copy of every value list the fields here read from.
 * Without it the freeze would stop at the edge of this table: somebody renaming
 * an option on the settings screen would change what a section published last
 * month says, and that is exactly what publication is supposed to rule out.
 *
 * The copy is not what gets read day to day. A stored value still resolves
 * through the live item while it is still there, so an option somebody
 * relabelled reads with its new label. The copy is what answers when the item
 * has gone, and it is what travels when the section is installed somewhere else.
 *
 * Only the lists the fields actually point at are copied. The rest of the
 * account's lists have nothing to do with this section.
 *
 * The labels are stored as they sit in the database, translation key and all,
 * rather than translated on the way in. Translating here would bake the
 * language of whoever pressed the button into the snapshot.
 */
class PublishSectionVersion
{
    public function __construct(
        private readonly User $user,
        private readonly SectionVersion $sectionVersion,
        private ?string $changeNote = null,
    ) {}

    public function execute(): SectionVersion
    {
        $this->sanitize();
        $this->validate();
        $this->publish();
        $this->log();

        return $this->sectionVersion;
    }

    private function sanitize(): void
    {
        $this->changeNote = TextSanitizer::nullablePlainText($this->changeNote);
    }

    private function validate(): void
    {
        if ($this->sectionVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section version is in another account.');
        }

        if ($this->sectionVersion->status !== VersionStatusEnum::Draft) {
            throw new PublishedVersionIsFrozen("Version {$this->sectionVersion->version_number} is not a draft.");
        }

        if ($this->sectionVersion->previous_version_id !== null && $this->changeNote === null) {
            throw new InvalidArgumentException('Publishing a new version of a section needs a note saying what changed.');
        }
    }

    private function publish(): void
    {
        DB::transaction(function (): void {
            $this->sectionVersion->status = VersionStatusEnum::Published;
            $this->sectionVersion->change_note = $this->changeNote;
            $this->sectionVersion->resolved_value_lists = $this->resolveValueLists();
            $this->sectionVersion->published_at = now();
            $this->sectionVersion->save();

            $section = $this->sectionVersion->section;
            $section->published_version_id = $this->sectionVersion->id;
            $section->save();
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveValueLists(): array
    {
        $ids = $this->sectionVersion->fields()->whereNotNull('value_list_id')->pluck('value_list_id')->unique();

        $lists = ValueList::query()->whereIn('id', $ids)->get();

        $snapshot = [];

        foreach ($lists as $list) {
            $snapshot[$list->id] = [
                'id' => $list->id,
                'key' => $list->key,
                'name' => $list->getRawOriginal('name'),
                'name_translation_key' => $list->name_translation_key,
                'items' => $list->items()->orderBy('position')->get()->map(fn (ValueListItem $item): array => [
                    'id' => $item->id,
                    'key' => $item->key,
                    'label' => $item->getRawOriginal('label'),
                    'label_translation_key' => $item->label_translation_key,
                    'position' => $item->position,
                    'is_active' => $item->is_active,
                ])->all(),
            ];
        }

        return $snapshot;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionVersionPublished,
            parameters: [
                'name' => $this->sectionVersion->section->name,
                'version' => $this->sectionVersion->version_number,
            ],
            loggable: $this->sectionVersion->section,
        )->onQueue('low');
    }
}
