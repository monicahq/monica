<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DefaultTemplateEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<TemplateVersion>
 */
class TemplateVersionFactory extends Factory
{
    protected $model = TemplateVersion::class;

    /**
     * A version detached from the account of its template describes nothing, so
     * the account is taken from the template rather than made beside it.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Every template version has at least one page, so a version built here
     * comes with the one the product supplies.
     *
     * The row is written with the query builder rather than through the model,
     * since a version built as already published refuses a page the way the
     * real thing does, and the page of such a version was put there before it
     * was frozen.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (TemplateVersion $version): void {
            DB::table('template_version_pages')->insert([
                'uuid' => (string) Str::uuid(),
                'account_id' => $version->account_id,
                'template_version_id' => $version->id,
                'key' => TemplateVersionPage::SUPPLIED_KEY,
                'label' => null,
                'label_translation_key' => DefaultTemplateEnum::PAGE_LABEL,
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Indicate that the version has been frozen, which is what every rule about
     * refusing a write leans on.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VersionStatusEnum::Published,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the version is no longer offered for a new record, while
     * every record already on it stays exactly as it is.
     */
    public function retired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VersionStatusEnum::Retired,
            'published_at' => now(),
            'retired_at' => now(),
        ]);
    }

    /**
     * Indicate that records point at the version, which is what makes the cost
     * of retiring it visible and what stops the template being deleted.
     */
    public function used(int $times = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'record_count' => $times,
        ]);
    }
}
