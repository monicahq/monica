<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateDefaultValueLists;
use App\Enums\DefaultPageEnum;
use App\Enums\DefaultSectionEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\ValueListEnum;
use App\Models\EntityType;
use App\Models\Section;
use App\Models\Template;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Give an account the Animal template beside Basic animal, on the Animals type it already holds, and
 * the list it reads from. It runs after sign up and in a backfill, so a second run adds nothing.
 */
class SupplyAnimalTemplate implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        $refusal = $this->refusal();

        if ($refusal !== null) {
            Log::warning("Skipped the animal template for account {$this->user->account_id}: {$refusal}.");

            return;
        }

        new CreateDefaultValueLists(user: $this->user, lists: [ValueListEnum::NeuterStatuses])->execute();

        new CreateDefaultTemplate(user: $this->user, supplied: DefaultTemplateEnum::Animal)->handle();
    }

    private function refusal(): ?string
    {
        $supplied = DefaultTemplateEnum::Animal;

        $template = Template::query()
            ->withTrashed()
            ->where('account_id', $this->user->account_id)
            ->where('is_system', false)
            ->where('slug', $supplied->value)
            ->exists();

        if ($template) {
            return "its own template {$supplied->value} holds the slug";
        }

        $sections = array_map(
            fn (DefaultSectionEnum $section): string => $section->value,
            array_merge(...array_map(fn (DefaultPageEnum $page): array => $page->sections(), $supplied->pages())),
        );

        $section = Section::query()
            ->withTrashed()
            ->where('account_id', $this->user->account_id)
            ->where('is_system', false)
            ->whereIn('slug', $sections)
            ->value('slug');

        if ($section !== null) {
            return "its own section {$section} holds the slug";
        }

        $slug = $supplied->entityType()['slug'] ?? '';

        $held = EntityType::query()->where('account_id', $this->user->account_id)->where('slug', $slug)->exists();
        $deleted = EntityType::query()->onlyTrashed()->where('account_id', $this->user->account_id)->where('slug', $slug)->exists();

        if (! $held && $deleted) {
            return "its {$slug} type was deleted";
        }

        return null;
    }
}
