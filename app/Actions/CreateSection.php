<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\SlugBuilder;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Define a reusable section: "Address", "Social networks", "Medical follow up".
 */
class CreateSection
{
    private Section $section;

    public function __construct(
        private readonly User $user,
        private string $name,
        private ?string $description = null,
        private ?string $slug = null,
        private ?string $icon = null,
        private ?string $colour = null,
        private readonly ?string $locale = null,
        private readonly bool $isActive = true,
    ) {}

    public function execute(): Section
    {
        $this->sanitize();
        $this->create();
        $this->log();

        return $this->section;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
        $this->slug = TextSanitizer::nullablePlainText($this->slug);
        $this->icon = TextSanitizer::nullablePlainText($this->icon);
        $this->colour = TextSanitizer::nullablePlainText($this->colour);
    }

    private function create(): void
    {
        DB::transaction(function (): void {
            $this->section = Section::query()->create([
                'account_id' => $this->user->account_id,
                'name' => $this->name,
                'name_translation_key' => null,
                'description' => $this->description,
                'slug' => $this->slug(),
                'icon' => $this->icon,
                'colour' => $this->colour,
                'is_system' => false,
                'is_active' => $this->isActive,
                'published_version_id' => null,
                'locale' => $this->locale ?? $this->user->locale ?? config('app.locale'),
            ]);

            SectionVersion::query()->create([
                'account_id' => $this->user->account_id,
                'section_id' => $this->section->id,
                'version_number' => 1,
                'status' => VersionStatusEnum::Draft,
                'change_note' => null,
                'resolved_value_lists' => null,
                'published_at' => null,
                'retired_at' => null,
                'previous_version_id' => null,
                'usage_count' => 0,
            ]);
        });
    }

    private function slug(): string
    {
        return SlugBuilder::build(
            wanted: $this->slug,
            derivedFrom: $this->name,
            maxLength: Section::SLUG_LENGTH,
            whenEmpty: 'section',
            isTaken: fn (string $slug): bool => Section::query()
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
            action: UserActionEnum::SectionCreation,
            parameters: ['name' => $this->section->name],
            loggable: $this->section,
        );
    }
}
