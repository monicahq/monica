<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\CreateDefaultValueLists;
use App\Enums\DefaultPageEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\ValueListEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Models\Account;
use App\Models\EntityType;
use App\Models\Section;
use App\Models\Template;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

#[Signature('monica:seed-animal-template {--account= : Id of one account, every account when left out}')]
#[Description('Give existing accounts the Animal type and the Basic animal template new accounts start with')]
class SeedAnimalTemplateCommand extends Command
{
    private int $seeded = 0;

    private int $alreadyDone = 0;

    private int $skipped = 0;

    public function handle(): int
    {
        $this->seeded = 0;
        $this->alreadyDone = 0;
        $this->skipped = 0;

        $id = $this->option('account');

        if ($id !== null && ! Account::query()->whereKey($id)->exists()) {
            $this->error("No account has the id {$id}.");

            return self::FAILURE;
        }

        Account::query()
            ->when($id !== null, fn (Builder $query): Builder => $query->whereKey($id))
            ->orderBy('id')
            ->each(fn (Account $account) => $this->seed($account));

        $this->info("Seeded {$this->seeded}, already done {$this->alreadyDone}, skipped {$this->skipped}.");

        return self::SUCCESS;
    }

    private function seed(Account $account): void
    {
        $this->line("Account {$account->id}…");

        $user = User::query()->where('account_id', $account->id)->first();

        if ($user === null) {
            $this->line('  Skipped: nobody signs in to it.');
            $this->skipped++;

            return;
        }

        if ($this->hasTheSuppliedTemplate($account)) {
            $this->line('  Already has the Basic animal template.');
            $this->alreadyDone++;

            return;
        }

        $held = $this->slugTheUserHolds($account);

        if ($held !== null) {
            $this->line("  Skipped: the slug {$held} is already used by one of its own.");
            $this->skipped++;

            return;
        }

        DB::transaction(function () use ($user): void {
            new CreateDefaultValueLists(user: $user, lists: [ValueListEnum::AnimalSexes])->execute();
            new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();
        });

        $this->line('  Seeded the Animal type and the Basic animal template.');
        $this->seeded++;
    }

    private function hasTheSuppliedTemplate(Account $account): bool
    {
        return Template::query()
            ->withTrashed()
            ->where('account_id', $account->id)
            ->where('is_system', true)
            ->where('slug', DefaultTemplateEnum::BasicAnimal->value)
            ->exists();
    }

    private function slugTheUserHolds(Account $account): ?string
    {
        $template = DefaultTemplateEnum::BasicAnimal;

        if (Template::query()->withTrashed()->where('account_id', $account->id)->where('slug', $template->value)->exists()) {
            return $template->value;
        }

        $type = $template->entityType()['slug'] ?? '';

        if (EntityType::query()->withTrashed()->where('account_id', $account->id)->where('slug', $type)->exists()) {
            return $type;
        }

        $sections = array_map(
            fn ($section): string => $section->value,
            array_merge(...array_map(fn (DefaultPageEnum $page): array => $page->sections(), $template->pages())),
        );

        $section = Section::query()
            ->withTrashed()
            ->where('account_id', $account->id)
            ->whereIn('slug', $sections)
            ->value('slug');

        return $section === null ? null : (string) $section;
    }
}
