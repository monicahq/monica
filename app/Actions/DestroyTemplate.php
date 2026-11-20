<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a template, which TemplatePolicy allows while no record points at any of its versions and
 * the account keeps another contact template.
 *
 * The delete is soft and the structure stays with it. A vault default naming one of its versions is
 * moved, in the same transaction, to the template a new vault would start from for the same kind of
 * record. Without that, the vault would have nothing for a new record to start from, and every way of
 * adding one would be refused. The default is removed only when no such template is left.
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

        $this->user->authorize('delete', $this->template);
    }

    private function destroy(): void
    {
        $this->name = $this->template->name;

        DB::transaction(function (): void {
            $defaults = VaultDefaultTemplate::query()
                ->whereIn('structure_version_id', $this->template->versions()->select('structure_versions.id'))
                ->get();

            foreach ($defaults as $default) {
                $this->fallBackToTheStartingPoint($default);
            }

            $this->template->delete();
        });
    }

    private function fallBackToTheStartingPoint(VaultDefaultTemplate $default): void
    {
        $startingPoint = Template::startingPointFor(
            accountId: $this->user->account_id,
            appliesTo: $default->applies_to,
            entityTypeId: $default->entity_type_id,
            exceptTemplateId: $this->template->id,
        );

        if ($startingPoint === null) {
            $default->delete();

            return;
        }

        $default->update(['structure_version_id' => $startingPoint->structure->published_version_id]);
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
