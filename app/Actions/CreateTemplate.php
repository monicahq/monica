<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DefaultPageEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\SlugBuilder;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Describe what a record holds: what a contact page shows, what an Animal is worth writing down
 * about.
 */
class CreateTemplate
{
    private Template $template;

    public function __construct(
        private readonly User $user,
        private string $name,
        private readonly TemplateAppliesToEnum $appliesTo = TemplateAppliesToEnum::Contact,
        private readonly ?EntityType $entityType = null,
        private ?string $description = null,
        private ?string $slug = null,
        private readonly ?string $locale = null,
        private readonly bool $isActive = true,
    ) {}

    public function execute(): Template
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->template;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
        $this->slug = TextSanitizer::nullablePlainText($this->slug);
    }

    private function validate(): void
    {
        if ($this->entityType !== null && $this->entityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity type is in another account.');
        }

        if ($this->appliesTo->needsEntityType() && $this->entityType === null) {
            throw new InvalidArgumentException('A template describing an entity has to name the type it describes.');
        }
    }

    private function create(): void
    {
        $positions = Template::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('position');

        DB::transaction(function () use ($positions): void {
            $this->template = Template::query()->create([
                'account_id' => $this->user->account_id,
                'applies_to' => $this->appliesTo,
                'entity_type_id' => $this->appliesTo->needsEntityType() ? $this->entityType?->id : null,
                'name' => $this->name,
                'name_translation_key' => null,
                'description' => $this->description,
                'slug' => $this->slug(),
                'is_system' => false,
                'is_active' => $this->isActive,
                'published_version_id' => null,
                'locale' => $this->locale ?? $this->user->locale ?? config('app.locale'),
                'position' => ($positions->max() ?? 0) + 1,
            ]);

            $version = TemplateVersion::query()->create([
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

            TemplateVersionPage::query()->create([
                'account_id' => $this->user->account_id,
                'template_version_id' => $version->id,
                'key' => TemplateVersionPage::SUPPLIED_KEY,
                'label' => null,
                'label_translation_key' => DefaultPageEnum::General->label(),
                'position' => 1,
            ]);
        });
    }

    private function slug(): string
    {
        return SlugBuilder::build(
            wanted: $this->slug,
            derivedFrom: $this->name,
            maxLength: Template::SLUG_LENGTH,
            whenEmpty: 'template',
            isTaken: fn (string $slug): bool => Template::query()
                ->withTrashed()
                ->where('account_id', $this->user->account_id)
                ->where('slug', $slug)
                ->exists(),
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateCreation,
            parameters: ['name' => $this->template->name],
            loggable: $this->template,
        )->onQueue('low');
    }
}
