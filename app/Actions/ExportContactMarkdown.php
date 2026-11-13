<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use App\ViewModels\Vaults\ContactMarkdownViewModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Hand a contact over as one Markdown document, for somebody to keep or to give to an assistant of
 * their choice. Every surface reads the same document, so the file, the clipboard, the API and the
 * MCP server never disagree.
 */
class ExportContactMarkdown
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
        private readonly bool $reachesSensitiveFields = false,
    ) {}

    public function execute(): string
    {
        $this->validate();

        $document = new ContactMarkdownViewModel(
            user: $this->user,
            vault: $this->contact->vault,
            contact: $this->contact,
            reachesSensitiveFields: $this->reachesSensitiveFields,
        )->document();

        $this->log();

        return $document;
    }

    private function validate(): void
    {
        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactExportedAsMarkdown,
            vault: $this->contact->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
        );
    }
}
