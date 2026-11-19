<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateDefaultValueLists;
use App\Enums\DefaultPageEnum;
use App\Enums\DefaultSectionEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\ValueListEnum;
use App\Models\Section;
use App\Models\Template;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Give an account the four contact templates it may pick beside Basic contact, and the value lists
 * they read from. It runs after sign up and in a backfill, so a second run adds nothing.
 */
class SupplyContactTemplates implements ShouldQueue
{
    use Queueable;

    private const array TEMPLATES = [
        DefaultTemplateEnum::Essentials,
        DefaultTemplateEnum::FriendsAndFamily,
        DefaultTemplateEnum::ProfessionalNetwork,
        DefaultTemplateEnum::FullPortrait,
    ];

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        new CreateDefaultValueLists(user: $this->user, lists: [
            ValueListEnum::Hobbies,
            ValueListEnum::CommunicationChannels,
            ValueListEnum::Industries,
            ValueListEnum::AreasOfExpertise,
        ])->execute();

        foreach (self::TEMPLATES as $supplied) {
            $held = $this->slugTheAccountHolds($supplied);

            if ($held !== null) {
                Log::warning("Skipped the {$supplied->value} template for account {$this->user->account_id}: its own {$held} holds the slug.");

                continue;
            }

            new CreateDefaultTemplate(user: $this->user, supplied: $supplied)->handle();
        }
    }

    private function slugTheAccountHolds(DefaultTemplateEnum $supplied): ?string
    {
        $template = Template::query()
            ->withTrashed()
            ->where('account_id', $this->user->account_id)
            ->where('is_system', false)
            ->where('slug', $supplied->value)
            ->exists();

        if ($template) {
            return "template {$supplied->value}";
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

        return $section === null ? null : "section {$section}";
    }
}
