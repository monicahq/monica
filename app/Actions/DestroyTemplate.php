<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteATemplateInUse;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a template, which is only allowed while no record points at any of its versions.
 *
 * The delete is soft and the structure stays with it. A vault default naming one of its versions
 * is removed in the same transaction, since a new record would otherwise be given a version whose
 * template is gone, and the vault falls back to the account's default the way a vault with no
 * default of its own already does.
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
            VaultDefaultTemplate::query()
                ->whereIn('structure_version_id', $this->template->versions()->select('structure_versions.id'))
                ->delete();

            $this->template->delete();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->template,
        )->onQueue('low');
    }
}
