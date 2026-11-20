<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DefaultPageEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Add a nature a life event can be recorded as, with the structure its custom fields will live in.
 *
 * The structure is created here rather than when somebody first opens the field builder, and its
 * first version is published rather than left a draft. That is what lets a life event of a brand new
 * type be recorded straight away: the life event names the version its type publishes, so a type with
 * nothing published is a type no life event can be written on. The version holds one page and no
 * sections, which renders a life event carrying no custom fields.
 */
class CreateLifeEventType
{
    private LifeEventType $lifeEventType;

    public function __construct(
        private readonly User $user,
        private readonly LifeEventCategory $lifeEventCategory,
        private string $name,
        private ?string $description = null,
        private readonly ?string $icon = null,
        private readonly ?string $colour = null,
        private readonly bool $updatesLastInTouch = true,
    ) {}

    public function execute(): LifeEventType
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->lifeEventType;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
    }

    private function validate(): void
    {
        if ($this->lifeEventCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event category is in another account.');
        }
    }

    private function create(): void
    {
        $positions = LifeEventType::query()
            ->where('life_event_category_id', $this->lifeEventCategory->id)
            ->pluck('position');

        DB::transaction(function () use ($positions): void {
            $this->lifeEventType = LifeEventType::query()->create([
                'account_id' => $this->user->account_id,
                'life_event_category_id' => $this->lifeEventCategory->id,
                'key' => null,
                'name' => $this->name,
                'name_translation_key' => null,
                'description' => $this->description,
                'icon' => $this->icon,
                'colour' => $this->colour,
                'is_system' => false,
                'is_active' => true,
                'updates_last_in_touch' => $this->updatesLastInTouch,
                'position' => ($positions->max() ?? 0) + 1,
            ]);

            $structure = Structure::query()->create([
                'account_id' => $this->user->account_id,
                'owner_type' => LifeEventType::class,
                'owner_id' => $this->lifeEventType->id,
                'applies_to' => StructureAppliesToEnum::LifeEvent,
                'entity_type_id' => null,
                'published_version_id' => null,
            ]);

            $version = StructureVersion::query()->create([
                'account_id' => $this->user->account_id,
                'structure_id' => $structure->id,
                'version_number' => 1,
                'status' => VersionStatusEnum::Draft,
                'change_note' => null,
                'published_at' => null,
                'retired_at' => null,
                'previous_version_id' => null,
                'record_count' => 0,
            ]);

            StructureVersionPage::query()->create([
                'account_id' => $this->user->account_id,
                'structure_version_id' => $version->id,
                'key' => StructureVersionPage::SUPPLIED_KEY,
                'label' => null,
                'label_translation_key' => DefaultPageEnum::General->label(),
                'position' => 1,
            ]);

            new PublishStructureVersion(user: $this->user, structureVersion: $version)->execute();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventTypeCreation,
            parameters: ['name' => $this->lifeEventType->name],
            loggable: $this->lifeEventType,
        );
    }
}
