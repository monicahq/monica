<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SupportMessageKindEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Enums\UserActionEnum;
use App\Helpers\SupportAttachmentWriter;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Write to the support team in a request already opened. Whatever its status, the request goes back
 * to the administrators' queue, which is also how a solved request is reopened under its number.
 */
class AddSupportMessage
{
    private SupportMessage $message;

    private string $cleanBody;

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

            $previous = $this->supportRequest->status;

            $this->supportRequest->status = SupportRequestStatusEnum::ToHandle;
            $this->supportRequest->solved_at = null;
            $this->supportRequest->last_activity_at = now();
            $this->supportRequest->save();

            if ($previous !== SupportRequestStatusEnum::ToHandle) {
                SupportStatusChange::query()->create([
                    'support_request_id' => $this->supportRequest->id,
                    'from_status' => $previous,
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
}
