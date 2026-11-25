<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StructureAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Put a contact on another template, or on another published version of its own, after checking the
 * template is one a contact may use. The move itself, and what it writes to the log, is
 * MoveRecordToStructureVersion's.
 */
class ChangeContactTemplate
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
        private readonly Template $template,
        private readonly ?StructureVersion $structureVersion = null,
    ) {}

    public function execute(): Contact
    {
        $this->validate();

        $moved = new MoveRecordToStructureVersion(
            user: $this->user,
            record: $this->contact,
            structureVersion: $this->structureVersion ?? $this->template->structure->publishedVersion,
        )->execute();

        return $moved->refresh();
    }

    public static function refusal(Template $template, ?StructureVersion $structureVersion = null): ?string
    {
        $structure = $template->structure;

        if ($structure->applies_to !== StructureAppliesToEnum::Contact) {
            return __('This template describes another kind of record.');
        }

        if (! $template->is_active) {
            return __('This template is turned off, so no contact can be put on it.');
        }

        if ($structure->published_version_id === null) {
            return __('This template has no published version yet.');
        }

        if (! $structureVersion instanceof StructureVersion) {
            return null;
        }

        if ($structureVersion->structure_id !== $structure->id) {
            return __('That version of the template is not published.');
        }

        if ($structureVersion->status !== VersionStatusEnum::Published) {
            return __('That version of the template is not published.');
        }

        return null;
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }

        if ($this->template->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }

        $refusal = self::refusal($this->template, $this->structureVersion);

        if ($refusal !== null) {
            throw new InvalidArgumentException($refusal);
        }
    }
}
