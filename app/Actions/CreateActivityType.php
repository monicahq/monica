<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DefaultPageEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Add a nature an activity can be recorded as, with the structure its custom fields will live in.
 *
 * The structure is created here rather than when somebody first opens the field builder, and its
 * first version is published rather than left a draft. That is what lets an activity of a brand new
 * type be recorded straight away: the activity names the version its type publishes, so a type with
 * nothing published is a type no activity can be written on. The version holds one page and no
 * sections, which renders an activity carrying no custom fields.
 */
class CreateActivityType
{
    private ActivityType $activityType;

    public function __construct(
        private readonly User $user,
        private readonly ActivityCategory $activityCategory,
        private string $name,
        private ?string $description = null,
        private readonly ?string $icon = null,
        private readonly ?string $colour = null,
    ) {}

    public function execute(): ActivityType
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->activityType;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
    }

    private function validate(): void
    {
        if ($this->activityCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity category is in another account.');
        }
    }

    private function create(): void
    {
        $positions = ActivityType::query()
            ->where('activity_category_id', $this->activityCategory->id)
            ->pluck('position');

        DB::transaction(function () use ($positions): void {
            $this->activityType = ActivityType::query()->create([
                'account_id' => $this->user->account_id,
                'activity_category_id' => $this->activityCategory->id,
                'key' => null,
                'name' => $this->name,
                'name_translation_key' => null,
                'description' => $this->description,
                'icon' => $this->icon,
                'colour' => $this->colour,
                'is_system' => false,
                'is_active' => true,
                'position' => ($positions->max() ?? 0) + 1,
            ]);

            $structure = Structure::query()->create([
                'account_id' => $this->user->account_id,
                'owner_type' => ActivityType::class,
                'owner_id' => $this->activityType->id,
                'applies_to' => StructureAppliesToEnum::Activity,
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
            action: UserActionEnum::ActivityTypeCreation,
            parameters: ['name' => $this->activityType->name],
            loggable: $this->activityType,
        )->onQueue('low');
    }
}
