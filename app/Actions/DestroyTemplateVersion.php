<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Throw away a draft of a template.
 *
 * A draft is work in progress and nothing points at it, so it goes freely. A
 * published version does not: it is retired instead, and the model refuses the
 * deletion rather than trusting this action to remember.
 *
 * The assembly goes with it, and every section it carried is now in one template version
 * fewer.
 */
class DestroyTemplateVersion
{
    private Template $template;

    private int $versionNumber;

    public function __construct(
        private readonly User $user,
        private readonly TemplateVersion $templateVersion,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->templateVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template version is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->template = $this->templateVersion->template;
        $this->versionNumber = $this->templateVersion->version_number;

        $sectionVersionIds = $this->templateVersion->assembly->pluck('section_version_id');

        DB::transaction(function () use ($sectionVersionIds): void {
            $this->templateVersion->delete();

            SectionVersion::query()
                ->whereIn('id', $sectionVersionIds)
                ->where('usage_count', '>', 0)
                ->decrement('usage_count');
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateVersionDeletion,
            parameters: [
                'name' => $this->template->name,
                'version' => $this->versionNumber,
            ],
            loggable: $this->template,
        )->onQueue('low');
    }
}
