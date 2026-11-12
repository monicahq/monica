<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\SupportMessageKindEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogAdministratorAction;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use App\Models\User;
use App\Notifications\SupportReplySent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Answer a support request: the reply is signed, emailed to the user, and moves the request to the
 * status chosen with it. The administrator capacity is checked here because MCP has no route.
 */
class SendSupportReply
{
    private SupportMessage $message;

    private string $cleanBody;

    public function __construct(
        private readonly User $administrator,
        private readonly SupportRequest $supportRequest,
        private readonly string $body,
        private readonly SupportRequestStatusEnum $statusAfter = SupportRequestStatusEnum::WaitingOnUser,
    ) {}

    public function execute(): SupportMessage
    {
        $this->validate();
        $this->sanitize();
        $this->create();
        $this->log();
        $this->notify();

        return $this->message;
    }

    private function validate(): void
    {
        if (! $this->administrator->isAdministrator()) {
            throw new ModelNotFoundException('This support request does not exist.');
        }
    }

    private function sanitize(): void
    {
        $this->cleanBody = TextSanitizer::plainText($this->body);
    }

    private function create(): void
    {
        DB::transaction(function (): void {
            $this->message = SupportMessage::query()->create([
                'support_request_id' => $this->supportRequest->id,
                'author_user_id' => $this->administrator->id,
                'author_name' => $this->administrator->name,
                'kind' => SupportMessageKindEnum::Reply,
                'body' => $this->cleanBody,
            ]);

            $previous = $this->supportRequest->status;

            $this->supportRequest->status = $this->statusAfter;
            $this->supportRequest->solved_at = $this->statusAfter === SupportRequestStatusEnum::Solved ? now() : null;
            $this->supportRequest->last_activity_at = now();
            $this->supportRequest->save();

            if ($previous !== $this->statusAfter) {
                SupportStatusChange::query()->create([
                    'support_request_id' => $this->supportRequest->id,
                    'from_status' => $previous,
                    'to_status' => $this->statusAfter,
                    'user_id' => $this->administrator->id,
                    'user_name' => $this->administrator->name,
                ]);
            }
        });
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::SupportReplySent,
            account: $this->supportRequest->account,
            parameters: ['reference' => $this->supportRequest->reference, 'name' => $this->supportRequest->user->name ?? ''],
            subject: $this->supportRequest,
        )->onQueue('low');
    }

    private function notify(): void
    {
        $user = $this->supportRequest->user;

        if ($user === null) {
            return;
        }

        $user->notify(new SupportReplySent(
            reference: $this->supportRequest->reference,
            subject: $this->supportRequest->subject,
            administratorName: $this->administrator->name,
            body: $this->cleanBody,
            url: route('support.requests.show', $this->supportRequest),
        ));
    }
}
