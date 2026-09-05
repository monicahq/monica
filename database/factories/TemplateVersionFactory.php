<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DefaultPageEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<TemplateVersion> */
class TemplateVersionFactory extends Factory
{
    protected $model = TemplateVersion::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'template_id' => Template::factory(),
            'account_id' => fn (array $attributes): int => Template::query()->findOrFail($attributes['template_id'])->account_id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Draft,
            'change_note' => null,
            'published_at' => null,
            'retired_at' => null,
            'previous_version_id' => null,
            'record_count' => 0,
        ];
    }

    #[Override]
    public function configure(): static
    {
        return $this->afterCreating(function (TemplateVersion $version): void {
            DB::table('template_version_pages')->insert([
                'uuid' => (string) Str::uuid(),
                'account_id' => $version->account_id,
                'template_version_id' => $version->id,
                'key' => TemplateVersionPage::SUPPLIED_KEY,
                'label' => null,
                'label_translation_key' => DefaultPageEnum::General->label(),
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VersionStatusEnum::Published,
            'published_at' => now(),
        ]);
    }

    public function retired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VersionStatusEnum::Retired,
            'published_at' => now(),
            'retired_at' => now(),
        ]);
    }

    public function used(int $times = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'record_count' => $times,
        ]);
    }
}
