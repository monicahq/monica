<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SupportMessageKindEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Enums\UserActionEnum;
use App\Helpers\SupportAttachmentWriter;
use App\Helpers\SupportAvailability;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use App\Models\User;
use App\Notifications\SupportMessageAdded;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Write to the support team in a request already opened. Whatever its status, the request goes back
 * to the administrators' queue, which is also how a solved request is reopened under its number, and
 * every other active administrator is emailed that the user wrote again.
 */
class AddSupportMessage
{
    private SupportMessage $message;

    private string $cleanBody;

    private SupportRequestStatusEnum $previousStatus;

    /** @param  list<UploadedFile>  $files */
    public function __construct(
        private readonly User $user,
        private readonly SupportRequest $supportRequest,
        private readonly string $body,
        private readonly array $files = [],
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
        if ($this->supportRequest->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This support request is in another account.');
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
                'author_user_id' => $this->user->id,
                'author_name' => $this->user->name,
                'kind' => SupportMessageKindEnum::Message,
                'body' => $this->cleanBody,
            ]);

            foreach ($this->files as $file) {
                SupportAttachmentWriter::store($this->message, $this->user->account_id, $file);
            }

            $this->previousStatus = $this->supportRequest->status;

            $this->supportRequest->status = SupportRequestStatusEnum::ToHandle;
            $this->supportRequest->solved_at = null;
            $this->supportRequest->last_activity_at = now();
            $this->supportRequest->save();

            if ($this->previousStatus !== SupportRequestStatusEnum::ToHandle) {
                SupportStatusChange::query()->create([
                    'support_request_id' => $this->supportRequest->id,
                    'from_status' => $this->previousStatus,
                    'to_status' => SupportRequestStatusEnum::ToHandle,
                    'user_id' => $this->user->id,
                    'user_name' => $this->user->name,
                ]);
            }
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SupportMessageAdded,
            parameters: ['subject' => $this->supportRequest->subject],
            loggable: $this->supportRequest,
        );
    }

    private function notify(): void
    {
        if (! SupportAvailability::isEnabled()) {
            return;
        }

        $administrators = SupportAvailability::administrators()
            ->reject(fn (User $administrator): bool => $administrator->id === $this->user->id);

        Notification::send($administrators, new SupportMessageAdded(
            reference: $this->supportRequest->reference,
            subject: $this->supportRequest->subject,
            accountId: $this->user->account_id,
            authorName: $this->user->name,
            body: $this->cleanBody,
            hasAttachments: $this->files !== [],
            reopened: $this->previousStatus === SupportRequestStatusEnum::Solved,
            url: route('admin.support.show', $this->supportRequest),
        ));
    }
}
