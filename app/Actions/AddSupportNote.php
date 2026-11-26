<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\SupportMessageKindEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Leave a note on a support request for the other administrators. The user is never shown it and
 * never told it exists, and it changes nothing about where the request stands.
 */
class AddSupportNote
{
    private SupportMessage $note;

    private string $trimmedBody;

    public function __construct(
        private readonly User $administrator,
        private readonly SupportRequest $supportRequest,
        private readonly string $body,
    ) {}

    public function execute(): SupportMessage
    {
        $this->validate();
        $this->sanitize();
        $this->create();
        $this->log();

        return $this->note;
    }

    private function validate(): void
    {
        if (! $this->administrator->isAdministrator()) {
            throw new ModelNotFoundException('This support request does not exist.');
        }
    }

    private function sanitize(): void
    {
        $this->trimmedBody = mb_trim($this->body);
    }

    private function create(): void
    {
        $this->note = SupportMessage::query()->create([
            'support_request_id' => $this->supportRequest->id,
            'author_user_id' => $this->administrator->id,
            'author_name' => $this->administrator->name,
            'kind' => SupportMessageKindEnum::Note,
            'body' => $this->trimmedBody,
        ]);
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::SupportNoteAdded,
            account: $this->supportRequest->account,
            parameters: ['reference' => $this->supportRequest->reference, 'name' => $this->supportRequest->user->name ?? ''],
            subject: $this->supportRequest,
        )->onQueue('low');
    }
}
