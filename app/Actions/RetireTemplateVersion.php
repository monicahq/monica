<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stop offering a version of a template to new records.
 *
 * Retiring is not deleting and it is not editing. Every record already on the
 * version stays on it and renders exactly what it always did. What stops is the
 * version being handed to anything new.
 *
 * A retired version cannot go on being the one the template offers by default,
 * so the template stops pointing at it. Until another version is published,
 * that template has nothing to give a new record, which is deliberate: it is
 * visible, and the way out is to publish something.
 */
class RetireTemplateVersion
{
    public function __construct(
        private readonly User $user,
        private readonly TemplateVersion $templateVersion,
    ) {}

    public function execute(): TemplateVersion
    {
        $this->validate();
        $this->retire();
        $this->log();

        return $this->templateVersion;
    }

    private function validate(): void
    {
        if ($this->templateVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template version is in another account.');
        }

        if ($this->templateVersion->status !== VersionStatusEnum::Published) {
            throw new InvalidArgumentException('Only a published version can be retired. A draft is thrown away instead.');
        }
    }

    private function retire(): void
    {
        DB::transaction(function (): void {
            $this->templateVersion->status = VersionStatusEnum::Retired;
            $this->templateVersion->retired_at = now();
            $this->templateVersion->save();

            $template = $this->templateVersion->template;

            if ($template->published_version_id === $this->templateVersion->id) {
                $template->published_version_id = null;
                $template->save();
            }
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateVersionRetired,
            parameters: [
                'name' => $this->templateVersion->template->name,
                'version' => $this->templateVersion->version_number,
            ],
            loggable: $this->templateVersion->template,
        )->onQueue('low');
    }
}
