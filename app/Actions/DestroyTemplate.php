<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DefaultTemplateEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteATemplateInUse;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a template, which is only allowed while no record points at any of its versions.
 *
 * The delete is soft and the structure stays with it. A vault default naming one of its versions is
 * moved, in the same transaction, to the account's system template for the same kind of record,
 * which is what a new vault starts from. Without that, the vault would have nothing for a new
 * contact to start from, and every way of adding one would be refused. The default is removed only
 * when the account has no such template left.
 */
class DestroyTemplate
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly Template $template,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->template->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }

        $records = (int) $this->template->versions()->sum('record_count');

        if ($records > 0) {
            throw new CannotDeleteATemplateInUse("This template still renders {$records} records.");
        }
    }

    private function destroy(): void
    {
        $this->name = $this->template->name;

        DB::transaction(function (): void {
            $defaults = VaultDefaultTemplate::query()
                ->whereIn('structure_version_id', $this->template->versions()->select('structure_versions.id'))
                ->get();

            foreach ($defaults as $default) {
                $this->fallBackToTheSystemTemplate($default);
            }

            $this->template->delete();
        });
    }

    private function fallBackToTheSystemTemplate(VaultDefaultTemplate $default): void
    {
        $systemTemplate = Template::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_system', true)
            ->whereIn('slug', array_map(
                fn (DefaultTemplateEnum $supplied): string => $supplied->value,
                array_filter(DefaultTemplateEnum::cases(), fn (DefaultTemplateEnum $supplied): bool => $supplied->isVaultDefault()),
            ))
            ->whereKeyNot($this->template->id)
            ->whereHas('structure', function (Builder $query) use ($default): void {
                $query->where('applies_to', $default->applies_to)
                    ->where('entity_type_id', $default->entity_type_id)
                    ->whereNotNull('published_version_id');
            })
            ->first();

        if ($systemTemplate === null) {
            $default->delete();

            return;
        }

        $default->update(['structure_version_id' => $systemTemplate->structure->published_version_id]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->template,
        );
    }
}
